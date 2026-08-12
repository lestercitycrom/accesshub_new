<?php

declare(strict_types=1);

namespace App\Delivery\Services;

use App\Delivery\Concerns\NormalizesDeliveryPlatforms;
use App\Delivery\DTO\DeliveryActionResult;
use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Enums\DeliveryPasswordType;
use App\Delivery\Models\DeliveryEvent;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Models\DeliveryOrderItem;
use App\Delivery\Models\DeliveryPlatformInstruction;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\DTO\IssuanceResult;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Settings\Services\SettingsService;
use App\Domain\Telegram\Enums\TelegramRole;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DeliveryOrderService
{
	use NormalizesDeliveryPlatforms;

	public function __construct(
		private readonly IssueService $issueService,
		private readonly FakePasswordFactory $fakePasswordFactory,
		private readonly SettingsService $settings,
	) {}

	public function createFromCustomerInput(string $orderNumber, string $customerEmail, string $platform, ?string $game = null): DeliveryOrder
	{
		$orderNumber = trim($orderNumber);
		$customerEmail = strtolower(trim($customerEmail));
		$platform = $this->normalizePlatform($platform);
		$game = trim((string) $game) ?: null;

		return DB::transaction(function () use ($orderNumber, $customerEmail, $platform, $game): DeliveryOrder {
			$order = DeliveryOrder::query()->create([
				'token' => $this->makeUniqueToken(),
				'order_number' => $orderNumber,
				'customer_email' => $customerEmail,
				'platform' => $platform,
				'game' => $game,
				'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
				'token_expires_at' => now()->addHours((int) config('delivery.link_ttl_hours', 72)),
				'connection_attempts_limit' => (int) config('delivery.connection_attempts_limit', 3),
			]);

			$this->recordEvent($order, 'order_created', payload: [
				'order_number' => $orderNumber,
				'customer_email' => $customerEmail,
				'platform' => $platform,
				'game' => $game,
			]);

			return $order;
		});
	}

	public function assignAccount(DeliveryOrder $order, int $operatorTelegramId, string $game, ?string $issuePlatform = null, ?int $accountId = null): DeliveryActionResult
	{
		$order->refresh();

		if ($this->isCompleted($order)) {
			return DeliveryActionResult::fail($this->completedMessage());
		}

		if ($order->isExpired()) {
			$this->markExpired($order);
			return DeliveryActionResult::fail('Delivery link is expired.');
		}

		// Re-issue ("Выдать другой аккаунт"): remember the currently assigned
		// account so its use can be returned once a new one is issued (P.1).
		$previousAccountId = $order->account_id !== null ? (int) $order->account_id : null;

		$game = trim($game);
		$issuePlatform = $this->normalizePlatform($issuePlatform ?: (string) $order->platform);

		if ($game === '' || $issuePlatform === '') {
			return DeliveryActionResult::fail('Game and platform are required.');
		}

		// A customer can open the delivery link after the same order number was
		// already issued through the regular bot flow. Attach that exact issuance
		// to the empty delivery card instead of spending another account use or
		// incorrectly reporting that no inventory exists.
		if ($previousAccountId === null) {
			$attached = $this->attachExistingIssuance(
				$order,
				$operatorTelegramId,
				$game,
				$issuePlatform,
				$accountId,
			);
			if ($attached !== null) {
				return $attached;
			}
		}

		$result = null;
		$attemptMessages = [];
		$issuedPlatform = $issuePlatform;

		foreach ($this->issuePlatformCandidates($issuePlatform) as $candidatePlatform) {
			$result = $this->issueService->issue(
				telegramId: $operatorTelegramId,
				orderId: (string) $order->order_number,
				game: $game,
				platform: $candidatePlatform,
				qty: 1,
				allowedRoles: [TelegramRole::OPERATOR, TelegramRole::DELIVERY_OPERATOR, TelegramRole::ADMIN],
				accountId: $accountId,
				allowRepeatOrder: $previousAccountId !== null,
			);

			if ($result->ok()) {
				$issuedPlatform = $this->canonicalIssuePlatformLabel($candidatePlatform);
				break;
			}

			$attemptMessages[$candidatePlatform] = $result->message();

			if (!$this->shouldTryNextIssuePlatform($result)) {
				break;
			}
		}

		if ($result === null || !$result->ok()) {
			$this->recordEvent($order, 'account_assignment_failed', 'telegram', (string) $operatorTelegramId, [
				'game' => $game,
				'issue_platform' => $issuePlatform,
				'attempted_issue_platforms' => array_keys($attemptMessages),
				'message' => $result?->message(),
			]);

			return DeliveryActionResult::fail(
				$this->formatAssignmentFailureMessage($game, $issuePlatform, $attemptMessages)
			);
		}

		$item = $result->items[0] ?? null;
		if (!is_array($item)) {
			return DeliveryActionResult::fail('Issue service returned no account.');
		}

		$accountId = (int) $item['account_id'];
		$issuance = Issuance::query()
			->where('order_id', $order->order_number)
			->where('account_id', $accountId)
			->latest('issued_at')
			->first();

		$requiresConnection = $this->requiresConnectionCode((string) $order->platform);
		$passwordType = $requiresConnection ? DeliveryPasswordType::FAKE : DeliveryPasswordType::REAL;
		$displayPassword = $requiresConnection
			? $this->fakePasswordFactory->make()
			: (string) $item['password'];

		$order->forceFill([
			'game' => $game,
			'issue_platform' => $issuedPlatform,
			'status' => $requiresConnection
				? DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE
				: DeliveryOrderStatus::ACCOUNT_ASSIGNED,
			'account_id' => $accountId,
			'issuance_id' => $issuance?->id,
			'operator_telegram_id' => $operatorTelegramId,
			'display_login' => (string) $item['login'],
			'display_password' => $displayPassword,
			'display_password_type' => $passwordType,
		])->save();

		$this->recordEvent($order, 'account_assigned', 'telegram', (string) $operatorTelegramId, [
			'game' => $game,
			'platform' => $order->platform,
			'issue_platform' => $issuedPlatform,
			'account_id' => $accountId,
			'issuance_id' => $issuance?->id,
			'password_type' => $passwordType->value,
		]);

		// P.1: a re-issue replaced a previously assigned account — return its use
		// to the pool so accounts are not wasted (mirrors replaceAccount).
		if ($previousAccountId !== null && $previousAccountId !== $accountId) {
			$this->restoreAccountUse($previousAccountId);
			$this->recordEvent($order, 'account_reissued', 'telegram', (string) $operatorTelegramId, [
				'previous_account_id' => $previousAccountId,
				'new_account_id' => $accountId,
			]);
		}

		return DeliveryActionResult::ok('Account assigned.');
	}

	private function attachExistingIssuance(
		DeliveryOrder $order,
		int $operatorTelegramId,
		string $game,
		string $issuePlatform,
		?int $requestedAccountId,
	): ?DeliveryActionResult {
		return DB::transaction(function () use ($order, $operatorTelegramId, $game, $issuePlatform, $requestedAccountId): ?DeliveryActionResult {
			$lockedOrder = DeliveryOrder::query()->lockForUpdate()->find($order->id);
			if ($lockedOrder === null || $lockedOrder->account_id !== null) {
				return null;
			}

			$candidatePlatforms = collect($this->issuePlatformCandidates($issuePlatform))
				->map(fn ($platform): string => $this->normalizePlatform((string) $platform))
				->unique()
				->all();

			$issuances = Issuance::query()
				->where('order_id', (string) $lockedOrder->order_number)
				->where('game', $game)
				->when($requestedAccountId !== null, static fn ($query) => $query->where('account_id', $requestedAccountId))
				->latest('issued_at')
				->lockForUpdate()
				->get();

			foreach ($issuances as $issuance) {
				if (!empty($issuance->payload['replaced'])) {
					continue;
				}

				$alreadyAttached = DeliveryOrder::query()
					->where('issuance_id', $issuance->id)
					->whereKeyNot($lockedOrder->id)
					->exists()
					|| DeliveryOrderItem::query()->where('issuance_id', $issuance->id)->exists();
				if ($alreadyAttached) {
					continue;
				}

				$account = Account::query()->lockForUpdate()->find($issuance->account_id);
				if ($account === null || $account->status !== AccountStatus::ACTIVE) {
					continue;
				}

				$accountPlatforms = collect(is_array($account->platform) ? $account->platform : [$account->platform])
					->filter()
					->map(fn ($platform): string => $this->normalizePlatform((string) $platform))
					->unique()
					->all();
				if (array_intersect($candidatePlatforms, $accountPlatforms) === []) {
					continue;
				}

				$requiresConnection = $this->requiresConnectionCode((string) $lockedOrder->platform);
				$passwordType = $requiresConnection ? DeliveryPasswordType::FAKE : DeliveryPasswordType::REAL;
				$lockedOrder->forceFill([
					'game' => $game,
					'issue_platform' => $this->canonicalIssuePlatformLabel((string) $issuance->platform),
					'status' => $requiresConnection
						? DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE
						: DeliveryOrderStatus::ACCOUNT_ASSIGNED,
					'account_id' => $account->id,
					'issuance_id' => $issuance->id,
					'operator_telegram_id' => $operatorTelegramId,
					'display_login' => (string) $account->login,
					'display_password' => $requiresConnection
						? $this->fakePasswordFactory->make()
						: (string) $account->password,
					'display_password_type' => $passwordType,
				])->save();

				$this->recordEvent($lockedOrder, 'existing_issuance_attached', 'telegram', (string) $operatorTelegramId, [
					'game' => $game,
					'issue_platform' => $lockedOrder->issue_platform,
					'account_id' => $account->id,
					'issuance_id' => $issuance->id,
					'password_type' => $passwordType->value,
				]);

				$order->refresh();

				return DeliveryActionResult::ok('Existing order issuance attached.');
			}

			return null;
		});
	}

	private function restoreAccountUse(int $accountId): void
	{
		$account = Account::query()->find($accountId);
		if ($account === null) {
			return;
		}

		$account->available_uses = min(
			(int) $account->available_uses + 1,
			(int) $account->max_uses,
		);
		if ((int) $account->available_uses > 0 && $account->next_release_at !== null) {
			$account->next_release_at = null;
		}
		$account->save();
	}

	/**
	 * P.4: issue an ADDITIONAL game into the order as a new item (tab).
	 * The first game stays on the order via assignAccount(); this adds games 2..N.
	 */
	public function addGame(DeliveryOrder $order, int $operatorTelegramId, string $game, ?string $issuePlatform = null, ?int $accountId = null): DeliveryActionResult
	{
		$order->refresh();

		if (in_array($order->status, [DeliveryOrderStatus::CANCELLED, DeliveryOrderStatus::EXPIRED], true)) {
			return DeliveryActionResult::fail($this->completedMessage());
		}
		if ($order->isExpired()) {
			$this->markExpired($order);
			return DeliveryActionResult::fail('Delivery link is expired.');
		}

		$game = trim($game);
		$issuePlatform = $this->normalizePlatform($issuePlatform ?: (string) $order->platform);
		if ($game === '' || $issuePlatform === '') {
			return DeliveryActionResult::fail('Game and platform are required.');
		}

		$result = null;
		$attemptMessages = [];
		$issuedPlatform = $issuePlatform;

		foreach ($this->issuePlatformCandidates($issuePlatform) as $candidatePlatform) {
			$result = $this->issueService->issue(
				telegramId: $operatorTelegramId,
				orderId: (string) $order->order_number,
				game: $game,
				platform: $candidatePlatform,
				qty: 1,
				allowedRoles: [TelegramRole::OPERATOR, TelegramRole::DELIVERY_OPERATOR, TelegramRole::ADMIN],
				accountId: $accountId,
				allowRepeatOrder: true,
			);

			if ($result->ok()) {
				$issuedPlatform = $this->canonicalIssuePlatformLabel($candidatePlatform);
				break;
			}

			$attemptMessages[$candidatePlatform] = $result->message();
			if (!$this->shouldTryNextIssuePlatform($result)) {
				break;
			}
		}

		if ($result === null || !$result->ok()) {
			return DeliveryActionResult::fail(
				$this->formatAssignmentFailureMessage($game, $issuePlatform, $attemptMessages)
			);
		}

		$item = $result->items[0] ?? null;
		if (!is_array($item)) {
			return DeliveryActionResult::fail('Issue service returned no account.');
		}

		$newAccountId = (int) $item['account_id'];
		$issuance = Issuance::query()
			->where('order_id', $order->order_number)
			->where('account_id', $newAccountId)
			->latest('issued_at')
			->first();

		$requiresConnection = $this->requiresConnectionCode($issuePlatform);
		$passwordType = $requiresConnection ? DeliveryPasswordType::FAKE : DeliveryPasswordType::REAL;
		$displayPassword = $requiresConnection
			? $this->fakePasswordFactory->make()
			: (string) $item['password'];

		$position = (int) ($order->items()->max('position') ?? 0) + 1;

		$newItem = DeliveryOrderItem::query()->create([
			'delivery_order_id' => $order->id,
			'position' => $position,
			'platform' => $issuePlatform,
			'issue_platform' => $issuedPlatform,
			'game' => $game,
			'status' => $requiresConnection
				? DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE
				: DeliveryOrderStatus::ACCOUNT_ASSIGNED,
			'account_id' => $newAccountId,
			'issuance_id' => $issuance?->id,
			'operator_telegram_id' => $operatorTelegramId,
			'display_login' => (string) $item['login'],
			'display_password' => $displayPassword,
			'display_password_type' => $passwordType,
			'connection_attempts_limit' => (int) config('delivery.connection_attempts_limit', 3),
		]);

		$this->recordEvent($order, 'item_added', 'telegram', (string) $operatorTelegramId, [
			'item_id' => $newItem->id,
			'position' => $position,
			'game' => $game,
			'issue_platform' => $issuedPlatform,
			'account_id' => $newAccountId,
			'password_type' => $passwordType->value,
		]);

		return DeliveryActionResult::ok('Game added.');
	}

	public function replaceAccount(DeliveryOrder|DeliveryOrderItem $holder, int $operatorTelegramId, string $reason): DeliveryActionResult
	{
		$holder->refresh();
		$owner = $this->orderOf($holder);
		$ctx = $this->holderContext($holder);

		if ($this->isCompleted($holder)) {
			return DeliveryActionResult::fail($this->completedMessage());
		}

		$reason = trim($reason);
		if ($reason === '') {
			return DeliveryActionResult::fail('Replacement reason is required.');
		}

		if ($holder->account_id === null || $holder->issuance_id === null || $holder->game === null || $holder->issue_platform === null) {
			return DeliveryActionResult::fail('Account is not assigned yet.');
		}

		return DB::transaction(function () use ($holder, $owner, $ctx, $operatorTelegramId, $reason): DeliveryActionResult {
			$holder->refresh();

			$original = Issuance::query()
				->with('account')
				->lockForUpdate()
				->find($holder->issuance_id);

			if ($original === null) {
				return DeliveryActionResult::fail('Original issuance was not found.');
			}

			if (!empty($original->payload['replaced'])) {
				return DeliveryActionResult::fail('This delivery issuance has already been replaced.');
			}

			$now = CarbonImmutable::now();
			$issuedAccountIds = Issuance::query()
				->where('order_id', $owner->order_number)
				->pluck('account_id')
				->filter()
				->map(fn ($accountId): int => (int) $accountId)
				->all();

			$replacement = Account::query()
				->where('game', (string) $holder->game)
				->where('status', AccountStatus::ACTIVE)
				->where(function ($query) use ($holder): void {
					foreach ($this->issuePlatformCandidates((string) $holder->issue_platform) as $index => $platform) {
						$method = $index === 0 ? 'whereJsonContains' : 'orWhereJsonContains';
						$query->{$method}('platform', $platform);
					}
				})
				->when($issuedAccountIds !== [], static function ($query) use ($issuedAccountIds): void {
					$query->whereNotIn('id', $issuedAccountIds);
				})
				->where(function ($query) use ($now): void {
					$query->where('available_uses', '>', 0)
						->orWhere(function ($nested) use ($now): void {
							$nested->whereNotNull('next_release_at')
								->where('next_release_at', '<=', $now->toDateTimeString());
						});
				})
				->orderByDesc('available_uses')
				->orderBy('id')
				->lockForUpdate()
				->first();

			if ($replacement === null) {
				return DeliveryActionResult::fail('No available accounts for replacement.');
			}

			$this->normalizeReplacementAvailability($replacement, $now);

			if ($replacement->available_uses <= 0) {
				return DeliveryActionResult::fail('Replacement account is not available.');
			}

			$replacement->available_uses -= 1;
			$cooldownDays = $this->settings->getInt(
				'cooldown_days',
				(int) config('accesshub.issuance.operator_cooldown_days', (int) config('accesshub.issuance.cooldown_days', 14)),
			);
			if ($replacement->available_uses === 0) {
				$replacement->next_release_at = $now->addDays($replacement->cooldownDays($cooldownDays));
			}
			$replacement->save();

			$originalAccount = $original->account;
			if ($reason !== 'dead' && $originalAccount !== null) {
				$originalAccount->available_uses = min(
					(int) $originalAccount->available_uses + 1,
					(int) $originalAccount->max_uses,
				);
				if ((int) $originalAccount->available_uses > 0 && $originalAccount->next_release_at !== null) {
					$originalAccount->next_release_at = null;
				}
				$originalAccount->save();
			}

			$original->payload = array_merge($original->payload ?? [], [
				'replaced' => true,
				'replaced_by_telegram_id' => $operatorTelegramId,
				'replaced_at' => $now->toDateTimeString(),
				'replacement_reason' => $reason,
				'replacement_delivery_order_id' => $owner->id,
			]);
			$original->save();

			$newIssuance = Issuance::query()->create([
				'order_id' => (string) $owner->order_number,
				'telegram_id' => $operatorTelegramId,
				'account_id' => $replacement->id,
				'game' => (string) $holder->game,
				'platform' => (string) $holder->issue_platform,
				'qty' => 1,
				'issued_at' => $now,
				'cooldown_until' => $replacement->available_uses === 0 ? $replacement->next_release_at : null,
				'payload' => [
					'is_replacement' => true,
					'original_issuance_id' => $original->id,
					'replacement_reason' => $reason,
					'delivery_order_id' => $owner->id,
				],
			]);

			AccountEvent::query()->create([
				'account_id' => $replacement->id,
				'telegram_id' => $operatorTelegramId,
				'type' => 'ISSUED',
				'payload' => [
					'order_id' => (string) $owner->order_number,
					'issuance_id' => $newIssuance->id,
					'is_replacement' => true,
					'replacement_reason' => $reason,
					'game' => (string) $holder->game,
					'platform' => (string) $holder->issue_platform,
					'delivery_order_id' => $owner->id,
				],
			]);

			$requiresConnection = $this->requiresConnectionCode((string) $holder->platform);
			$passwordType = $requiresConnection ? DeliveryPasswordType::FAKE : DeliveryPasswordType::REAL;
			$displayPassword = $requiresConnection
				? $this->fakePasswordFactory->make()
				: (string) $replacement->password;

			$holder->forceFill([
				'status' => $requiresConnection
					? DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE
					: DeliveryOrderStatus::ACCOUNT_ASSIGNED,
				'account_id' => $replacement->id,
				'issuance_id' => $newIssuance->id,
				'operator_telegram_id' => $operatorTelegramId,
				'display_login' => (string) $replacement->login,
				'display_password' => $displayPassword,
				'display_password_type' => $passwordType,
				'connection_attempts_used' => 0,
				'connection_locked_until' => null,
				'last_connection_code' => null,
				'last_connection_code_submitted_at' => null,
				'connected_at' => null,
			])->save();

			$this->recordEvent($owner, 'account_replaced', 'telegram', (string) $operatorTelegramId, $ctx + [
				'reason' => $reason,
				'old_account_id' => $original->account_id,
				'old_issuance_id' => $original->id,
				'new_account_id' => $replacement->id,
				'new_issuance_id' => $newIssuance->id,
				'password_type' => $passwordType->value,
			]);

			return DeliveryActionResult::ok('Replacement account assigned.');
		});
	}

	/**
	 * P.3: cancel the whole order (refund/chargeback). Revokes the client link
	 * (credentials are hidden by publicPayload once cancelled). Account uses are
	 * NOT returned to the pool — the credentials were already exposed to the buyer.
	 */
	public function cancelOrder(DeliveryOrder $order, int $operatorTelegramId, ?string $reason = null): DeliveryActionResult
	{
		$order->refresh();

		if ($order->status === DeliveryOrderStatus::CANCELLED) {
			return DeliveryActionResult::ok('Заказ уже отменён.');
		}

		$order->forceFill([
			'status' => DeliveryOrderStatus::CANCELLED,
			'cancelled_at' => now(),
		])->save();

		$order->items()->update([
			'status' => DeliveryOrderStatus::CANCELLED->value,
		]);

		$this->recordEvent($order, 'order_cancelled', 'telegram', (string) $operatorTelegramId, [
			'reason' => $reason,
		]);

		return DeliveryActionResult::ok('Заказ отменён.');
	}

	public function submitConnectionCode(DeliveryOrder|DeliveryOrderItem $holder, string $code): DeliveryActionResult
	{
		$holder->refresh();
		$owner = $this->orderOf($holder);
		$ctx = $this->holderContext($holder);

		if ($this->isCompleted($holder)) {
			return DeliveryActionResult::fail('This order is already connected.');
		}

		if ($owner->isExpired()) {
			$this->markExpired($owner);
			return DeliveryActionResult::fail('Delivery link is expired.');
		}

		if (!$this->requiresConnectionCode((string) $holder->platform)) {
			return DeliveryActionResult::fail('Connection code is not required for this platform.');
		}

		if ($holder->account_id === null) {
			return DeliveryActionResult::fail('Account is not assigned yet.');
		}

		if ($holder->connection_locked_until !== null && now()->lessThan($holder->connection_locked_until)) {
			$holder->forceFill(['status' => DeliveryOrderStatus::LOCKED_24H])->save();
			return DeliveryActionResult::fail('Connection attempts are locked.');
		}

		if ($holder->connection_locked_until !== null && now()->greaterThanOrEqualTo($holder->connection_locked_until)) {
			$holder->forceFill([
				'status' => DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE,
				'connection_attempts_used' => 0,
				'connection_locked_until' => null,
			])->save();

			$this->recordEvent($owner, 'connection_unlocked_after_timeout', payload: $ctx);
		}

		if ((int) $holder->connection_attempts_used >= (int) $holder->connection_attempts_limit) {
			$this->lockForRetry($holder);
			return DeliveryActionResult::fail('Connection attempts limit reached.');
		}

		$code = strtoupper(trim($code));
		if (!preg_match('/^[A-Z0-9]{6,8}$/', $code)) {
			return DeliveryActionResult::fail('Connection code must contain 6-8 letters or digits.');
		}

		$holder->forceFill([
			'status' => DeliveryOrderStatus::CONNECTION_CODE_SUBMITTED,
			'connection_attempts_used' => (int) $holder->connection_attempts_used + 1,
			'last_connection_code' => $code,
			'last_connection_code_submitted_at' => now(),
		])->save();

		$this->recordEvent($owner, 'connection_code_submitted', payload: $ctx + [
			'code' => $code,
			'attempts_used' => $holder->connection_attempts_used,
			'attempts_limit' => $holder->connection_attempts_limit,
		]);

		return DeliveryActionResult::ok('Connection code submitted.');
	}

	public function markOperatorConnecting(DeliveryOrder|DeliveryOrderItem $holder, int $operatorTelegramId): DeliveryActionResult
	{
		$holder->refresh();

		if ($this->isCompleted($holder)) {
			return DeliveryActionResult::fail($this->completedMessage());
		}

		$holder->forceFill([
			'status' => DeliveryOrderStatus::OPERATOR_CONNECTING,
			'operator_telegram_id' => $operatorTelegramId,
		])->save();

		$this->recordEvent($this->orderOf($holder), 'operator_connecting', 'telegram', (string) $operatorTelegramId, $this->holderContext($holder));

		return DeliveryActionResult::ok('Статус обновлен: оператор подключает.');
	}

	public function markConnected(DeliveryOrder|DeliveryOrderItem $holder, int $operatorTelegramId): DeliveryActionResult
	{
		$holder->refresh();

		if ($this->isCompleted($holder)) {
			return DeliveryActionResult::ok('Заказ уже подключен.');
		}

		$holder->forceFill([
			'status' => DeliveryOrderStatus::CONNECTED,
			'operator_telegram_id' => $operatorTelegramId,
			'connected_at' => now(),
		])->save();

		$this->recordEvent($this->orderOf($holder), 'connected', 'telegram', (string) $operatorTelegramId, $this->holderContext($holder));

		return DeliveryActionResult::ok('Статус обновлен: подключение выполнено.');
	}

	public function markConnectionFailed(DeliveryOrder|DeliveryOrderItem $holder, int $operatorTelegramId, ?string $reason = null): DeliveryActionResult
	{
		$holder->refresh();

		if ($this->isCompleted($holder)) {
			return DeliveryActionResult::fail($this->completedMessage());
		}

		$holder->forceFill([
			'status' => DeliveryOrderStatus::CONNECTION_FAILED,
			'operator_telegram_id' => $operatorTelegramId,
		])->save();

		$this->recordEvent($this->orderOf($holder), 'connection_failed', 'telegram', (string) $operatorTelegramId, $this->holderContext($holder) + [
			'reason' => $reason,
		]);

		return DeliveryActionResult::ok('Статус обновлен: ошибка подключения.');
	}

	public function grantExtraAttempts(DeliveryOrder|DeliveryOrderItem $holder, int $operatorTelegramId, int $amount): DeliveryActionResult
	{
		$holder->refresh();

		if ($this->isCompleted($holder)) {
			return DeliveryActionResult::fail($this->completedMessage());
		}

		$amount = max(1, $amount);

		$holder->forceFill([
			'connection_attempts_limit' => (int) $holder->connection_attempts_limit + $amount,
			'connection_locked_until' => null,
			'status' => $holder->account_id === null
				? DeliveryOrderStatus::WAITING_FOR_OPERATOR
				: DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE,
			'operator_telegram_id' => $operatorTelegramId,
		])->save();

		$this->recordEvent($this->orderOf($holder), 'extra_attempts_granted', 'telegram', (string) $operatorTelegramId, $this->holderContext($holder) + [
			'amount' => $amount,
			'attempts_limit' => $holder->connection_attempts_limit,
		]);

		return DeliveryActionResult::ok("Добавлено попыток: {$amount}.");
	}

	public function publicPayload(DeliveryOrder $order): array
	{
		$order->refresh();

		if ($order->isExpired()) {
			$this->markExpired($order);
			$order->refresh();
		}

		$instruction = DeliveryPlatformInstruction::query()
			->where('platform', $order->platform)
			->where('is_active', true)
			->first();

		// P.4: uniform tab list — the first game (order) + any additional items.
		$hideCreds = in_array($order->status, [
			DeliveryOrderStatus::EXPIRED,
			DeliveryOrderStatus::CANCELLED,
		], true);
		$items = [$this->serializeHolder($order, 0, $hideCreds)];
		foreach ($order->items as $item) {
			$items[] = $this->serializeHolder($item, (int) $item->position, $hideCreds);
		}

		return [
			'id' => $order->id,
			'status' => $order->status->value,
			'order_number' => $order->order_number,
			'customer_email' => $this->maskEmail((string) $order->customer_email),
			'platform' => $order->platform,
			'game' => $order->game,
			'items' => $items,
			'expires_at' => $order->token_expires_at?->toIso8601String(),
			// P.2: hide credentials once the link is expired or the order is
			// cancelled (client is shown "Link expired" / "Cancelled" instead).
			'account' => ($order->display_login !== null && !in_array($order->status, [
				DeliveryOrderStatus::EXPIRED,
				DeliveryOrderStatus::CANCELLED,
			], true)) ? [
				'login' => $order->display_login,
				'password' => $order->display_password,
				'password_type' => $order->display_password_type?->value,
			] : null,
			'connection' => [
				'required' => $this->requiresConnectionCode((string) $order->platform),
				'attempts_used' => (int) $order->connection_attempts_used,
				'attempts_limit' => (int) $order->connection_attempts_limit,
				'locked_until' => $order->connection_locked_until?->toIso8601String(),
				'last_submitted_at' => $order->last_connection_code_submitted_at?->toIso8601String(),
			],
			'instruction' => $instruction !== null ? [
				'title' => $instruction->title,
				'body' => $instruction->body,
			] : null,
			'tutorial_url' => $this->tutorialUrl((string) $order->platform),
			'polling_interval_seconds' => (int) config('delivery.polling_interval_seconds', 8),
		];
	}

	/**
	 * Uniform per-game payload for the client tabs (works for the order's first
	 * game and for additional items). `id` = 0 for the first game, item id otherwise.
	 *
	 * @return array<string, mixed>
	 */
	private function serializeHolder(DeliveryOrder|DeliveryOrderItem $holder, int $position, bool $hideCreds): array
	{
		$platform = (string) $holder->platform;
		$instruction = DeliveryPlatformInstruction::query()
			->where('platform', $platform)
			->where('is_active', true)
			->first();

		$hide = $hideCreds || in_array($holder->status, [
			DeliveryOrderStatus::EXPIRED,
			DeliveryOrderStatus::CANCELLED,
		], true);

		return [
			'id' => $holder instanceof DeliveryOrderItem ? (int) $holder->id : 0,
			'position' => $position,
			'game' => $holder->game,
			'platform' => $platform,
			'issue_platform' => $holder->issue_platform,
			'status' => $holder->status?->value,
			'account' => ($holder->display_login !== null && !$hide) ? [
				'login' => $holder->display_login,
				'password' => $holder->display_password,
				'password_type' => $holder->display_password_type?->value,
			] : null,
			'connection' => [
				'required' => $this->requiresConnectionCode($platform),
				'attempts_used' => (int) $holder->connection_attempts_used,
				'attempts_limit' => (int) $holder->connection_attempts_limit,
				'locked_until' => $holder->connection_locked_until?->toIso8601String(),
				'last_submitted_at' => $holder->last_connection_code_submitted_at?->toIso8601String(),
			],
			'instruction' => $instruction !== null ? [
				'title' => $instruction->title,
				'body' => $instruction->body,
			] : null,
			'tutorial_url' => $this->tutorialUrl($platform),
		];
	}

	private function tutorialUrl(string $platform): ?string
	{
		$map = (array) config('delivery.tutorial_urls', []);
		$platform = $this->normalizePlatform($platform);
		$url = $map[$platform] ?? null;

		return is_string($url) && $url !== '' ? $url : null;
	}

	public function requiresConnectionCode(string $platform): bool
	{
		$platform = $this->normalizePlatform($platform);

		return in_array($platform, array_map(
			fn ($item) => $this->normalizePlatform((string) $item),
			(array) config('delivery.connection_platforms', [])
		), true);
	}

	/**
	 * @param array<string, string|null> $attemptMessages
	 */
	private function formatAssignmentFailureMessage(string $game, string $issuePlatform, array $attemptMessages): string
	{
		$platforms = array_keys($attemptMessages);
		$messages = collect($attemptMessages)
			->filter()
			->unique()
			->values();
		$alreadyIssuedMessage = $messages->first(
			static fn ($message): bool => str_contains((string) $message, 'Повторная выдача запрещена')
		);
		if ($alreadyIssuedMessage !== null) {
			return (string) $alreadyIssuedMessage;
		}

		if (count($platforms) > 1) {
			return sprintf(
				'Не найден доступный аккаунт для игры "%s" на платформах: %s. Проверьте название игры или выберите платформу выдачи из списка доступных вариантов.',
				$game,
				implode(', ', $platforms),
			);
		}

		return (string) ($messages->first() ?? 'Account assignment failed.');
	}

	private function shouldTryNextIssuePlatform(IssuanceResult $result): bool
	{
		// Prefer machine-readable reason codes (A5). Fall back to legacy text
		// matching only when the reason is absent, so behaviour stays safe even
		// if some IssueService fail path has no reason set yet.
		$retryableReasons = [
			IssuanceResult::REASON_NO_ACCOUNTS,
			IssuanceResult::REASON_NO_AVAILABLE,
			IssuanceResult::REASON_INSUFFICIENT,
			IssuanceResult::REASON_STOLEN,
			IssuanceResult::REASON_ALREADY_ISSUED,
		];

		if ($result->reason() !== null) {
			return in_array($result->reason(), $retryableReasons, true);
		}

		$message = $result->message();
		if ($message === null) {
			return false;
		}

		return str_contains($message, 'Нет аккаунт')
			|| str_contains($message, 'Нет доступ')
			|| str_contains($message, 'Недостаточно доступ')
			|| str_contains($message, 'Украден')
			|| str_contains($message, 'уже выданы');
	}

	private function canonicalIssuePlatformLabel(string $platform): string
	{
		return match ($this->normalizePlatform($platform)) {
			'XBox' => 'Xbox',
			'2' => 'Nintendo Switch 2',
			default => $this->normalizePlatform($platform),
		};
	}

	private function normalizeReplacementAvailability(Account $account, CarbonImmutable $now): void
	{
		if ($account->next_release_at === null) {
			return;
		}

		$next = CarbonImmutable::parse($account->next_release_at);
		if ($now->greaterThanOrEqualTo($next)) {
			$account->available_uses = 1;
			$account->next_release_at = null;
		}
	}

	private function lockForRetry(DeliveryOrder|DeliveryOrderItem $holder): void
	{
		$holder->forceFill([
			'status' => DeliveryOrderStatus::LOCKED_24H,
			'connection_locked_until' => now()->addHours((int) config('delivery.connection_lock_hours', 24)),
		])->save();

		$this->recordEvent($this->orderOf($holder), 'connection_locked', payload: $this->holderContext($holder) + [
			'locked_until' => $holder->connection_locked_until?->toIso8601String(),
		]);
	}

	private function isCompleted(DeliveryOrder|DeliveryOrderItem $holder): bool
	{
		return $holder->status === DeliveryOrderStatus::CONNECTED;
	}

	/**
	 * The owning order for a holder (item → its order; order → itself).
	 * Used for order-level concerns: expiry and event ownership.
	 */
	private function orderOf(DeliveryOrder|DeliveryOrderItem $holder): DeliveryOrder
	{
		return $holder instanceof DeliveryOrderItem ? $holder->order : $holder;
	}

	/**
	 * @return array<string, mixed> Item context for event payloads.
	 */
	private function holderContext(DeliveryOrder|DeliveryOrderItem $holder): array
	{
		return $holder instanceof DeliveryOrderItem
			? ['item_id' => $holder->id, 'position' => $holder->position]
			: [];
	}

	private function completedMessage(): string
	{
		return 'Заказ уже подключен и завершен. Действия по нему зафиксированы.';
	}

	private function markExpired(DeliveryOrder $order): void
	{
		if ($order->status === DeliveryOrderStatus::EXPIRED) {
			return;
		}

		$order->forceFill(['status' => DeliveryOrderStatus::EXPIRED])->save();
		$this->recordEvent($order, 'expired');
	}

	private function recordEvent(
		DeliveryOrder $order,
		string $type,
		?string $actorType = null,
		?string $actorId = null,
		array $payload = [],
	): void {
		DeliveryEvent::query()->create([
			'delivery_order_id' => $order->id,
			'type' => $type,
			'actor_type' => $actorType,
			'actor_id' => $actorId,
			'payload' => $payload !== [] ? $payload : null,
			'created_at' => now(),
		]);
	}

	private function makeUniqueToken(): string
	{
		do {
			$token = Str::random(64);
		} while (DeliveryOrder::query()->where('token', $token)->exists());

		return $token;
	}

	private function maskEmail(string $email): string
	{
		if (!str_contains($email, '@')) {
			return $email;
		}

		[$name, $domain] = explode('@', $email, 2);
		$visible = mb_substr($name, 0, 2);

		return $visible . str_repeat('*', max(3, mb_strlen($name) - 2)) . '@' . $domain;
	}
}

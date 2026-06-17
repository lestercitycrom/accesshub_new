<?php

declare(strict_types=1);

namespace App\Delivery\Services;

use App\Delivery\DTO\DeliveryActionResult;
use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Enums\DeliveryPasswordType;
use App\Delivery\Models\DeliveryEvent;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Models\DeliveryPlatformInstruction;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Telegram\Enums\TelegramRole;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DeliveryOrderService
{
	public function __construct(
		private readonly IssueService $issueService,
		private readonly FakePasswordFactory $fakePasswordFactory,
	) {}

	public function createFromCustomerInput(string $orderNumber, string $customerEmail, string $platform): DeliveryOrder
	{
		$orderNumber = trim($orderNumber);
		$customerEmail = strtolower(trim($customerEmail));
		$platform = $this->normalizePlatform($platform);

		return DB::transaction(function () use ($orderNumber, $customerEmail, $platform): DeliveryOrder {
			$order = DeliveryOrder::query()->create([
				'token' => $this->makeUniqueToken(),
				'order_number' => $orderNumber,
				'customer_email' => $customerEmail,
				'platform' => $platform,
				'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
				'token_expires_at' => now()->addHours((int) config('delivery.link_ttl_hours', 72)),
				'connection_attempts_limit' => (int) config('delivery.connection_attempts_limit', 3),
			]);

			$this->recordEvent($order, 'order_created', payload: [
				'order_number' => $orderNumber,
				'customer_email' => $customerEmail,
				'platform' => $platform,
			]);

			return $order;
		});
	}

	public function assignAccount(DeliveryOrder $order, int $operatorTelegramId, string $game, ?string $issuePlatform = null, ?int $accountId = null): DeliveryActionResult
	{
		$order->refresh();

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
			);

			if ($result->ok()) {
				$issuedPlatform = $this->canonicalIssuePlatformLabel($candidatePlatform);
				break;
			}

			$attemptMessages[$candidatePlatform] = $result->message();

			if (!$this->shouldTryNextIssuePlatform($result->message())) {
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

		return DeliveryActionResult::ok('Account assigned.');
	}

	public function replaceAccount(DeliveryOrder $order, int $operatorTelegramId, string $reason): DeliveryActionResult
	{
		$order->refresh();

		$reason = trim($reason);
		if ($reason === '') {
			return DeliveryActionResult::fail('Replacement reason is required.');
		}

		if ($order->account_id === null || $order->issuance_id === null || $order->game === null || $order->issue_platform === null) {
			return DeliveryActionResult::fail('Account is not assigned yet.');
		}

		return DB::transaction(function () use ($order, $operatorTelegramId, $reason): DeliveryActionResult {
			$order->refresh();

			$original = Issuance::query()
				->with('account')
				->lockForUpdate()
				->find($order->issuance_id);

			if ($original === null) {
				return DeliveryActionResult::fail('Original issuance was not found.');
			}

			if (!empty($original->payload['replaced'])) {
				return DeliveryActionResult::fail('This delivery issuance has already been replaced.');
			}

			$now = CarbonImmutable::now();
			$issuedAccountIds = Issuance::query()
				->where('order_id', $order->order_number)
				->pluck('account_id')
				->filter()
				->map(fn ($accountId): int => (int) $accountId)
				->all();

			$replacement = Account::query()
				->where('game', (string) $order->game)
				->where('status', AccountStatus::ACTIVE)
				->where(function ($query) use ($order): void {
					foreach ($this->issuePlatformCandidates((string) $order->issue_platform) as $index => $platform) {
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
			$cooldownDays = (int) config('accesshub.issuance.operator_cooldown_days', (int) config('accesshub.issuance.cooldown_days', 14));
			if ($replacement->available_uses === 0) {
				$replacement->next_release_at = $now->addDays($cooldownDays);
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
				'replacement_delivery_order_id' => $order->id,
			]);
			$original->save();

			$newIssuance = Issuance::query()->create([
				'order_id' => (string) $order->order_number,
				'telegram_id' => $operatorTelegramId,
				'account_id' => $replacement->id,
				'game' => (string) $order->game,
				'platform' => (string) $order->issue_platform,
				'qty' => 1,
				'issued_at' => $now,
				'cooldown_until' => $replacement->available_uses === 0 ? $replacement->next_release_at : null,
				'payload' => [
					'is_replacement' => true,
					'original_issuance_id' => $original->id,
					'replacement_reason' => $reason,
					'delivery_order_id' => $order->id,
				],
			]);

			AccountEvent::query()->create([
				'account_id' => $replacement->id,
				'telegram_id' => $operatorTelegramId,
				'type' => 'ISSUED',
				'payload' => [
					'order_id' => (string) $order->order_number,
					'issuance_id' => $newIssuance->id,
					'is_replacement' => true,
					'replacement_reason' => $reason,
					'game' => (string) $order->game,
					'platform' => (string) $order->issue_platform,
					'delivery_order_id' => $order->id,
				],
			]);

			$requiresConnection = $this->requiresConnectionCode((string) $order->platform);
			$passwordType = $requiresConnection ? DeliveryPasswordType::FAKE : DeliveryPasswordType::REAL;
			$displayPassword = $requiresConnection
				? $this->fakePasswordFactory->make()
				: (string) $replacement->password;

			$order->forceFill([
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

			$this->recordEvent($order, 'account_replaced', 'telegram', (string) $operatorTelegramId, [
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

	public function submitConnectionCode(DeliveryOrder $order, string $code): DeliveryActionResult
	{
		$order->refresh();

		if ($order->isExpired()) {
			$this->markExpired($order);
			return DeliveryActionResult::fail('Delivery link is expired.');
		}

		if (!$this->requiresConnectionCode((string) $order->platform)) {
			return DeliveryActionResult::fail('Connection code is not required for this platform.');
		}

		if ($order->account_id === null) {
			return DeliveryActionResult::fail('Account is not assigned yet.');
		}

		if ($order->connection_locked_until !== null && now()->lessThan($order->connection_locked_until)) {
			$order->forceFill(['status' => DeliveryOrderStatus::LOCKED_24H])->save();
			return DeliveryActionResult::fail('Connection attempts are locked.');
		}

		if ($order->connection_locked_until !== null && now()->greaterThanOrEqualTo($order->connection_locked_until)) {
			$order->forceFill([
				'status' => DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE,
				'connection_attempts_used' => 0,
				'connection_locked_until' => null,
			])->save();

			$this->recordEvent($order, 'connection_unlocked_after_timeout');
		}

		if ((int) $order->connection_attempts_used >= (int) $order->connection_attempts_limit) {
			$this->lockForRetry($order);
			return DeliveryActionResult::fail('Connection attempts limit reached.');
		}

		$code = strtoupper(trim($code));
		if (!preg_match('/^[A-Z0-9]{6,8}$/', $code)) {
			return DeliveryActionResult::fail('Connection code must contain 6-8 letters or digits.');
		}

		$order->forceFill([
			'status' => DeliveryOrderStatus::CONNECTION_CODE_SUBMITTED,
			'connection_attempts_used' => (int) $order->connection_attempts_used + 1,
			'last_connection_code' => $code,
			'last_connection_code_submitted_at' => now(),
		])->save();

		$this->recordEvent($order, 'connection_code_submitted', payload: [
			'code' => $code,
			'attempts_used' => $order->connection_attempts_used,
			'attempts_limit' => $order->connection_attempts_limit,
		]);

		return DeliveryActionResult::ok('Connection code submitted.');
	}

	public function markOperatorConnecting(DeliveryOrder $order, int $operatorTelegramId): void
	{
		$order->forceFill([
			'status' => DeliveryOrderStatus::OPERATOR_CONNECTING,
			'operator_telegram_id' => $operatorTelegramId,
		])->save();

		$this->recordEvent($order, 'operator_connecting', 'telegram', (string) $operatorTelegramId);
	}

	public function markConnected(DeliveryOrder $order, int $operatorTelegramId): void
	{
		$order->forceFill([
			'status' => DeliveryOrderStatus::CONNECTED,
			'operator_telegram_id' => $operatorTelegramId,
			'connected_at' => now(),
		])->save();

		$this->recordEvent($order, 'connected', 'telegram', (string) $operatorTelegramId);
	}

	public function markConnectionFailed(DeliveryOrder $order, int $operatorTelegramId, ?string $reason = null): void
	{
		$order->forceFill([
			'status' => DeliveryOrderStatus::CONNECTION_FAILED,
			'operator_telegram_id' => $operatorTelegramId,
		])->save();

		$this->recordEvent($order, 'connection_failed', 'telegram', (string) $operatorTelegramId, [
			'reason' => $reason,
		]);
	}

	public function grantExtraAttempts(DeliveryOrder $order, int $operatorTelegramId, int $amount): void
	{
		$amount = max(1, $amount);

		$order->forceFill([
			'connection_attempts_limit' => (int) $order->connection_attempts_limit + $amount,
			'connection_locked_until' => null,
			'status' => $order->account_id === null
				? DeliveryOrderStatus::WAITING_FOR_OPERATOR
				: DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE,
			'operator_telegram_id' => $operatorTelegramId,
		])->save();

		$this->recordEvent($order, 'extra_attempts_granted', 'telegram', (string) $operatorTelegramId, [
			'amount' => $amount,
			'attempts_limit' => $order->connection_attempts_limit,
		]);
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

		return [
			'id' => $order->id,
			'status' => $order->status->value,
			'order_number' => $order->order_number,
			'customer_email' => $this->maskEmail((string) $order->customer_email),
			'platform' => $order->platform,
			'game' => $order->game,
			'expires_at' => $order->token_expires_at?->toIso8601String(),
			'account' => $order->display_login !== null ? [
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
			'polling_interval_seconds' => (int) config('delivery.polling_interval_seconds', 8),
		];
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
	 * @return array<int, string>
	 */
	private function issuePlatformCandidates(string $platform): array
	{
		$platform = $this->normalizePlatform($platform);

		return match ($platform) {
			'PlayStation' => ['PlayStation', 'PS5', 'PS4'],
			'Xbox' => ['Xbox', 'XBox', 'Xbox X', 'Xbox One'],
			'Nintendo' => ['Nintendo', 'Nintendo Switch 2', '2', 'Nintendo Switch 1'],
			'Nintendo Switch 1' => ['Nintendo Switch 1', 'Nintendo'],
			'Nintendo Switch 2' => ['Nintendo Switch 2', '2', 'Nintendo'],
			'Epic Games' => ['Epic Games', 'EpicGames', 'Epic'],
			default => [$platform],
		};
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

		if (count($platforms) > 1) {
			return sprintf(
				'Не найден доступный аккаунт для игры "%s" на платформах: %s. Проверьте название игры или выберите платформу выдачи из списка доступных вариантов.',
				$game,
				implode(', ', $platforms),
			);
		}

		return (string) ($messages->first() ?? 'Account assignment failed.');
	}

	private function shouldTryNextIssuePlatform(?string $message): bool
	{
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

	private function lockForRetry(DeliveryOrder $order): void
	{
		$order->forceFill([
			'status' => DeliveryOrderStatus::LOCKED_24H,
			'connection_locked_until' => now()->addHours((int) config('delivery.connection_lock_hours', 24)),
		])->save();

		$this->recordEvent($order, 'connection_locked', payload: [
			'locked_until' => $order->connection_locked_until?->toIso8601String(),
		]);
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

	private function normalizePlatform(string $platform): string
	{
		$platform = trim($platform);
		$lower = strtolower(str_replace([' ', '_', '-'], '', $platform));

		return match ($lower) {
			'ps', 'playstation' => 'PlayStation',
			'ps4' => 'PS4',
			'ps5' => 'PS5',
			'xb', 'xbox' => 'Xbox',
			'xboxx', 'xboxseriesx' => 'Xbox X',
			'xboxone' => 'Xbox One',
			'nintendo', 'switch', 'nintendoswitch' => 'Nintendo',
			'nintendo1', 'switch1', 'nintendoswitch1' => 'Nintendo Switch 1',
			'nintendo2', 'switch2', 'nintendoswitch2' => 'Nintendo Switch 2',
			'steam' => 'Steam',
			'epic', 'epicgames' => 'Epic Games',
			default => $platform,
		};
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

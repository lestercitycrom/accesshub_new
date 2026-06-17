<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Delivery\Models\DeliveryEvent;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class DeliveryOrdersController
{
	public function __construct(
		private readonly DeliveryOrderService $deliveryOrderService,
	) {}

	public function index(Request $request): JsonResponse
	{
		$user = $this->authorizedUser($request);
		if ($user instanceof JsonResponse) {
			return $user;
		}

		$q = trim((string) $request->query('q', ''));
		$highlightId = (int) $request->query('delivery_order', $request->query('id', 0));
		$limit = max(1, min(100, (int) $request->query('limit', 50)));

		$query = DeliveryOrder::query()
			->with(['operator', 'account', 'issuance']);

		if ($q !== '') {
			$like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
			$query->where(function ($nested) use ($like): void {
				$nested->where('order_number', 'like', $like)
					->orWhere('customer_email', 'like', $like)
					->orWhere('platform', 'like', $like)
					->orWhere('issue_platform', 'like', $like)
					->orWhere('game', 'like', $like)
					->orWhere('last_connection_code', 'like', $like);
			});
		}

		if ($highlightId > 0) {
			$query->orderByRaw('case when id = ? then 0 else 1 end', [$highlightId]);
		}

		$orders = $query
			->latest('created_at')
			->limit($limit)
			->get();

		return response()->json([
			'ok' => true,
			'items' => $orders
				->map(fn (DeliveryOrder $order): array => $this->serializeOrder($order))
				->all(),
		]);
	}

	public function options(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
	{
		$user = $this->authorizedUser($request);
		if ($user instanceof JsonResponse) {
			return $user;
		}

		$issuePlatform = trim((string) $request->query('issue_platform', ''));
		$gameSearch = trim((string) $request->query('game', ''));

		$platform = $issuePlatform !== '' ? $issuePlatform : (string) ($deliveryOrder->issue_platform ?? $deliveryOrder->platform);

		return response()->json([
			'ok' => true,
			'issue_platform_options' => $this->issuePlatformOptions($deliveryOrder),
			'available_games' => $this->availableGames($platform, $gameSearch),
			'available_accounts' => $this->availableAccounts($platform, $gameSearch),
		]);
	}

	public function assign(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
	{
		$user = $this->authorizedUser($request);
		if ($user instanceof JsonResponse) {
			return $user;
		}

		$game = trim((string) $request->input('game', ''));
		$issuePlatform = trim((string) $request->input('issue_platform', ''));
		$accountId = (int) $request->input('account_id', 0);

		if ($game === '') {
			return $this->actionResponse($deliveryOrder, false, 'Укажите игру для выдачи.', 422);
		}

		$result = $this->deliveryOrderService->assignAccount(
			$deliveryOrder,
			(int) $user->telegram_id,
			$game,
			$issuePlatform !== '' ? $issuePlatform : null,
			$accountId > 0 ? $accountId : null,
		);

		return $this->actionResponse(
			$deliveryOrder,
			$result->successful(),
			$result->message() ?? ($result->successful() ? 'Аккаунт выдан.' : 'Не удалось выдать аккаунт.'),
			$result->successful() ? 200 : 422,
		);
	}

	public function connecting(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
	{
		$user = $this->authorizedUser($request);
		if ($user instanceof JsonResponse) {
			return $user;
		}

		$this->deliveryOrderService->markOperatorConnecting($deliveryOrder, (int) $user->telegram_id);

		return $this->actionResponse($deliveryOrder, true, 'Оператор подключает.');
	}

	public function connected(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
	{
		$user = $this->authorizedUser($request);
		if ($user instanceof JsonResponse) {
			return $user;
		}

		$this->deliveryOrderService->markConnected($deliveryOrder, (int) $user->telegram_id);

		return $this->actionResponse($deliveryOrder, true, 'Подключено.');
	}

	public function failed(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
	{
		$user = $this->authorizedUser($request);
		if ($user instanceof JsonResponse) {
			return $user;
		}

		$this->deliveryOrderService->markConnectionFailed(
			$deliveryOrder,
			(int) $user->telegram_id,
			trim((string) $request->input('reason', '')) ?: null,
		);

		return $this->actionResponse($deliveryOrder, true, 'Отмечена ошибка подключения.');
	}

	public function extraAttempts(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
	{
		$user = $this->authorizedUser($request);
		if ($user instanceof JsonResponse) {
			return $user;
		}

		$amount = max(1, min(20, (int) $request->input('amount', 1)));

		$this->deliveryOrderService->grantExtraAttempts($deliveryOrder, (int) $user->telegram_id, $amount);

		return $this->actionResponse($deliveryOrder, true, "Добавлено попыток: {$amount}.");
	}

	public function replace(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
	{
		$user = $this->authorizedUser($request);
		if ($user instanceof JsonResponse) {
			return $user;
		}

		$reason = trim((string) $request->input('reason', ''));
		if ($reason === '') {
			return $this->actionResponse($deliveryOrder, false, 'Укажите причину замены.', 422);
		}

		$result = $this->deliveryOrderService->replaceAccount(
			$deliveryOrder,
			(int) $user->telegram_id,
			$reason,
		);

		return $this->actionResponse(
			$deliveryOrder,
			$result->successful(),
			$result->message() ?? ($result->successful() ? 'Замена выдана.' : 'Не удалось выдать замену.'),
			$result->successful() ? 200 : 422,
		);
	}

	private function actionResponse(DeliveryOrder $order, bool $ok, string $message, int $status = 200): JsonResponse
	{
		$order->refresh();
		$order->load(['operator', 'account', 'issuance']);

		return response()->json([
			'ok' => $ok,
			'message' => $message,
			'order' => $this->serializeOrder($order),
		], $status);
	}

	private function authorizedUser(Request $request): TelegramUser|JsonResponse
	{
		$telegramId = (int) $request->session()->get('webapp.telegram_id', 0);

		if ($telegramId <= 0) {
			return response()->json(['error' => 'Не инициализировано.'], 403);
		}

		$user = TelegramUser::query()
			->where('telegram_id', $telegramId)
			->first();

		if ($user === null || $user->is_active !== true) {
			return response()->json(['error' => 'Доступ запрещен.'], 403);
		}

		if (!in_array($user->role, [TelegramRole::OPERATOR, TelegramRole::DELIVERY_OPERATOR, TelegramRole::ADMIN], true)) {
			return response()->json(['error' => 'Недостаточно прав.'], 403);
		}

		return $user;
	}

	private function serializeOrder(DeliveryOrder $order): array
	{
		$operator = $order->operator;
		$operatorName = $operator
			? ($operator->username ? '@' . $operator->username : trim((string) ($operator->first_name . ' ' . $operator->last_name)))
			: null;
		$operatorName = $operatorName !== '' ? $operatorName : null;

		return [
			'id' => $order->id,
			'order_number' => $order->order_number,
			'customer_email' => $order->customer_email,
			'platform' => $order->platform,
			'issue_platform' => $order->issue_platform,
			'game' => $order->game,
			'status' => $order->status?->value,
			'status_label' => $this->statusLabel((string) $order->status?->value),
			'token_expires_at' => $order->token_expires_at?->toIso8601String(),
			'connected_at' => $order->connected_at?->toIso8601String(),
			'account_id' => $order->account_id,
			'issuance_id' => $order->issuance_id,
			'operator_telegram_id' => $order->operator_telegram_id,
			'operator_name' => $operatorName,
			'display_login' => $order->display_login,
			'display_password' => $order->display_password,
			'display_password_type' => $order->display_password_type?->value,
			'connection_attempts_used' => (int) $order->connection_attempts_used,
			'connection_attempts_limit' => (int) $order->connection_attempts_limit,
			'connection_locked_until' => $order->connection_locked_until?->toIso8601String(),
			'last_connection_code' => $order->last_connection_code,
			'last_connection_code_submitted_at' => $order->last_connection_code_submitted_at?->toIso8601String(),
			'public_url' => route('delivery.order.show', ['token' => $order->token], true),
			'issue_platform_options' => $this->issuePlatformOptions($order),
			'available_games' => $this->availableGames((string) ($order->issue_platform ?? $order->platform), ''),
			'available_accounts' => $this->availableAccounts((string) ($order->issue_platform ?? $order->platform), (string) $order->game),
			'replacements' => $this->replacementEvents($order),
		];
	}

	/**
	 * @return array<int, string>
	 */
	private function issuePlatformOptions(DeliveryOrder $order): array
	{
		$accountPlatforms = Account::query()
			->where('status', AccountStatus::ACTIVE)
			->distinct()
			->pluck('platform')
			->flatMap(fn ($platforms) => is_array($platforms) ? $platforms : (array) $platforms)
			->map(fn ($platform) => $this->canonicalIssuePlatformOption((string) $platform));

		return collect($this->preferredIssuePlatformOptions((string) $order->platform))
			->merge([$order->platform, $order->issue_platform])
			->merge((array) config('delivery.connection_platforms', []))
			->merge((array) config('delivery.direct_delivery_platforms', []))
			->merge($accountPlatforms)
			->filter()
			->map(fn ($platform) => $this->canonicalIssuePlatformOption((string) $platform))
			->unique()
			->values()
			->all();
	}

	/**
	 * @return array<int, array{name: string, accounts_count: int}>
	 */
	private function availableGames(string $platform, string $gameSearch): array
	{
		$platform = trim($platform);

		return Account::query()
			->select('game', DB::raw('COUNT(*) as accounts_count'))
			->where('status', AccountStatus::ACTIVE)
			->when($gameSearch !== '', static function ($query) use ($gameSearch): void {
				$query->where('game', 'like', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $gameSearch) . '%');
			})
			->where(function ($query): void {
				$query->where('available_uses', '>', 0)
					->orWhere(function ($nested): void {
						$nested->whereNotNull('next_release_at')
							->where('next_release_at', '<=', now());
					});
			})
			->where(function ($query) use ($platform): void {
				foreach ($this->issuePlatformCandidates($platform) as $index => $candidate) {
					$method = $index === 0 ? 'whereJsonContains' : 'orWhereJsonContains';
					$query->{$method}('platform', $candidate);
				}
			})
			->groupBy('game')
			->orderBy('game')
			->limit(100)
			->get()
			->map(fn ($row): array => [
				'name' => (string) $row->game,
				'accounts_count' => (int) $row->accounts_count,
			])
			->all();
	}

	/**
	 * @return array<int, array{id: int, login: string, available_uses: int}>
	 */
	private function availableAccounts(string $platform, string $game): array
	{
		$platform = trim($platform);
		$game = trim($game);

		if ($game === '') {
			return [];
		}

		return Account::query()
			->where('status', AccountStatus::ACTIVE)
			->where('game', $game)
			->where(function ($query): void {
				$query->where('available_uses', '>', 0)
					->orWhere(function ($nested): void {
						$nested->whereNotNull('next_release_at')
							->where('next_release_at', '<=', now());
					});
			})
			->where(function ($query) use ($platform): void {
				foreach ($this->issuePlatformCandidates($platform) as $index => $candidate) {
					$method = $index === 0 ? 'whereJsonContains' : 'orWhereJsonContains';
					$query->{$method}('platform', $candidate);
				}
			})
			->orderByDesc('available_uses')
			->orderBy('id')
			->limit(100)
			->get(['id', 'login', 'available_uses'])
			->map(fn (Account $account): array => [
				'id' => (int) $account->id,
				'login' => (string) $account->login,
				'available_uses' => (int) $account->available_uses,
			])
			->all();
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function replacementEvents(DeliveryOrder $order): array
	{
		return DeliveryEvent::query()
			->where('delivery_order_id', $order->id)
			->where('type', 'account_replaced')
			->latest('created_at')
			->limit(10)
			->get()
			->map(fn (DeliveryEvent $event): array => [
				'created_at' => $event->created_at?->toIso8601String(),
				'payload' => $event->payload ?? [],
			])
			->all();
	}

	/**
	 * @return array<int, string>
	 */
	private function preferredIssuePlatformOptions(string $platform): array
	{
		return match ($this->normalizePlatform($platform)) {
			'PlayStation' => ['PS5', 'PS4', 'PlayStation'],
			'Xbox' => ['Xbox', 'Xbox X', 'Xbox One'],
			'Nintendo' => ['Nintendo Switch 2', 'Nintendo Switch 1', 'Nintendo'],
			'Epic Games' => ['Epic Games', 'EpicGames', 'Epic'],
			default => [$this->normalizePlatform($platform)],
		};
	}

	/**
	 * @return array<int, string>
	 */
	private function issuePlatformCandidates(string $platform): array
	{
		return match ($this->normalizePlatform($platform)) {
			'PlayStation' => ['PlayStation', 'PS5', 'PS4'],
			'Xbox' => ['Xbox', 'XBox', 'Xbox X', 'Xbox One'],
			'Nintendo' => ['Nintendo', 'Nintendo Switch 2', '2', 'Nintendo Switch 1'],
			'Nintendo Switch 1' => ['Nintendo Switch 1', 'Nintendo'],
			'Nintendo Switch 2' => ['Nintendo Switch 2', '2', 'Nintendo'],
			'Epic Games' => ['Epic Games', 'EpicGames', 'Epic'],
			default => [$this->normalizePlatform($platform)],
		};
	}

	private function canonicalIssuePlatformOption(string $platform): string
	{
		return match ($this->normalizePlatform($platform)) {
			'2' => 'Nintendo Switch 2',
			default => $this->normalizePlatform($platform),
		};
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

	private function statusLabel(string $status): string
	{
		return match ($status) {
			'new' => 'Новый',
			'waiting_for_operator' => 'Ждет оператора',
			'account_assigned' => 'Аккаунт выдан',
			'waiting_for_connection_code' => 'Ждет код',
			'connection_code_submitted' => 'Код отправлен',
			'operator_connecting' => 'Оператор подключает',
			'connected' => 'Подключен',
			'connection_failed' => 'Ошибка подключения',
			'locked_24h' => 'Блок 24ч',
			'expired' => 'Истек',
			'cancelled' => 'Отменен',
			default => $status,
		};
	}
}

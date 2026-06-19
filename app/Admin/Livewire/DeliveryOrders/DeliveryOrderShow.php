<?php

declare(strict_types=1);

namespace App\Admin\Livewire\DeliveryOrders;

use App\Delivery\Concerns\NormalizesDeliveryPlatforms;
use App\Delivery\Models\DeliveryEvent;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Models\DeliveryOrderItem;
use App\Delivery\Services\DeliveryOrderService;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class DeliveryOrderShow extends Component
{
	use NormalizesDeliveryPlatforms;

	public DeliveryOrder $deliveryOrder;
	public string $operatorTelegramId = '';
	public string $game = '';
	public string $issuePlatform = '';
	public string $selectedAccountId = '';
	public string $extraAttempts = '1';
	public string $failReason = '';
	public string $replacementReason = 'wrong_password';
	public string $newGame = '';
	public string $newGamePlatform = '';

	public function mount(DeliveryOrder $deliveryOrder): void
	{
		Gate::authorize('admin');

		$this->deliveryOrder = $deliveryOrder;
		$this->refreshOrder();

		$this->game = (string) ($this->deliveryOrder->game ?? '');
		$this->issuePlatform = (string) ($this->deliveryOrder->issue_platform ?? $this->defaultIssuePlatform());
		$this->operatorTelegramId = (string) (
			$this->deliveryOrder->operator_telegram_id
			?? TelegramUser::query()->where('is_active', true)->orderBy('username')->value('telegram_id')
			?? ''
		);
	}

	public function updatedGame(): void
	{
		$this->selectedAccountId = '';
	}

	public function updatedIssuePlatform(): void
	{
		$this->selectedAccountId = '';
	}

	public function assignAccount(DeliveryOrderService $orders): void
	{
		Gate::authorize('admin');

		$telegramId = $this->selectedOperatorTelegramId();
		if ($telegramId === null) {
			$this->flashError('Выберите активного Telegram оператора.');
			return;
		}

		if (trim($this->game) === '') {
			$this->flashError('Укажите игру для выдачи.');
			return;
		}

		$accountId = (int) $this->selectedAccountId;

		$result = $orders->assignAccount(
			$this->deliveryOrder,
			$telegramId,
			trim($this->game),
			trim($this->issuePlatform) !== '' ? trim($this->issuePlatform) : null,
			$accountId > 0 ? $accountId : null,
		);

		$this->refreshOrder();

		if ($result->failed()) {
			$this->flashError($result->message() ?? 'Не удалось выдать аккаунт.');
			return;
		}

		session()->flash('message', $result->message() ?? 'Аккаунт выдан.');
	}

	public function addGame(DeliveryOrderService $orders): void
	{
		Gate::authorize('admin');

		$telegramId = $this->selectedOperatorTelegramId();
		if ($telegramId === null) {
			$this->flashError('Выберите активного Telegram оператора.');
			return;
		}
		if (trim($this->newGame) === '') {
			$this->flashError('Укажите игру для добавления.');
			return;
		}

		$result = $orders->addGame(
			$this->deliveryOrder,
			$telegramId,
			trim($this->newGame),
			trim($this->newGamePlatform) !== '' ? trim($this->newGamePlatform) : null,
		);

		$this->refreshOrder();

		if ($result->failed()) {
			$this->flashError($result->message() ?? 'Не удалось добавить игру.');
			return;
		}

		$this->newGame = '';
		session()->flash('message', $result->message() ?? 'Игра добавлена.');
	}

	public function cancelOrder(DeliveryOrderService $orders): void
	{
		$this->withOperator(function (int $telegramId) use ($orders): void {
			$result = $orders->cancelOrder($this->deliveryOrder, $telegramId, 'admin');
			$this->refreshOrder();
			$this->flashResult($result, 'Заказ отменён.');
		});
	}

	public function itemConnecting(int $itemId): void
	{
		$this->itemAction($itemId, fn ($s, $i, $t) => $s->markOperatorConnecting($i, $t), 'Статус обновлен: оператор подключает.');
	}

	public function itemConnected(int $itemId): void
	{
		$this->itemAction($itemId, fn ($s, $i, $t) => $s->markConnected($i, $t), 'Статус обновлен: подключено.');
	}

	public function itemFailed(int $itemId): void
	{
		$this->itemAction($itemId, fn ($s, $i, $t) => $s->markConnectionFailed($i, $t, 'admin'), 'Статус обновлен: ошибка.');
	}

	public function itemExtra(int $itemId): void
	{
		$this->itemAction($itemId, fn ($s, $i, $t) => $s->grantExtraAttempts($i, $t, 1), 'Добавлена попытка.');
	}

	public function itemReplace(int $itemId): void
	{
		$this->itemAction($itemId, fn ($s, $i, $t) => $s->replaceAccount($i, $t, 'dead'), 'Замена выдана.');
	}

	private function itemAction(int $itemId, callable $callback, string $okMessage): void
	{
		$this->withOperator(function (int $telegramId) use ($itemId, $callback, $okMessage): void {
			$item = DeliveryOrderItem::query()
				->where('id', $itemId)
				->where('delivery_order_id', $this->deliveryOrder->id)
				->first();

			if ($item === null) {
				$this->flashError('Игра не найдена в заказе.');
				return;
			}

			$result = $callback(app(DeliveryOrderService::class), $item, $telegramId);
			$this->refreshOrder();
			$this->flashResult($result, $okMessage);
		});
	}

	public function markOperatorConnecting(DeliveryOrderService $orders): void
	{
		$this->withOperator(function (int $telegramId) use ($orders): void {
			$result = $orders->markOperatorConnecting($this->deliveryOrder, $telegramId);
			$this->refreshOrder();
			$this->flashResult($result, 'Статус обновлен: оператор подключает.');
		});
	}

	public function markConnected(DeliveryOrderService $orders): void
	{
		$this->withOperator(function (int $telegramId) use ($orders): void {
			$result = $orders->markConnected($this->deliveryOrder, $telegramId);
			$this->refreshOrder();
			$this->flashResult($result, 'Статус обновлен: подключение выполнено.');
		});
	}

	public function markConnectionFailed(DeliveryOrderService $orders): void
	{
		$this->withOperator(function (int $telegramId) use ($orders): void {
			$result = $orders->markConnectionFailed(
				$this->deliveryOrder,
				$telegramId,
				trim($this->failReason) !== '' ? trim($this->failReason) : null,
			);

			$this->failReason = '';
			$this->refreshOrder();
			$this->flashResult($result, 'Статус обновлен: ошибка подключения.');
		});
	}

	public function grantExtraAttempts(DeliveryOrderService $orders): void
	{
		$this->withOperator(function (int $telegramId) use ($orders): void {
			$amount = max(1, (int) $this->extraAttempts);

			$result = $orders->grantExtraAttempts($this->deliveryOrder, $telegramId, $amount);
			$this->refreshOrder();
			$this->flashResult($result, "Добавлено попыток: {$amount}.");
		});
	}

	public function replaceAccount(DeliveryOrderService $orders): void
	{
		$this->withOperator(function (int $telegramId) use ($orders): void {
			$result = $orders->replaceAccount($this->deliveryOrder, $telegramId, $this->replacementReason);
			$this->refreshOrder();

			if ($result->failed()) {
				$this->flashError($result->message() ?? 'Не удалось выдать замену.');
				return;
			}

			session()->flash('message', $result->message() ?? 'Замена выдана.');
		});
	}

	/**
	 * @return Collection<int, TelegramUser>
	 */
	public function getOperatorsProperty(): Collection
	{
		return TelegramUser::query()
			->where('is_active', true)
			->orderBy('username')
			->get();
	}

	public function getIssuePlatformOptionsProperty(): array
	{
		$accountPlatforms = Account::query()
			->where('status', AccountStatus::ACTIVE)
			->distinct()
			->pluck('platform')
			->flatMap(fn ($platforms) => is_array($platforms) ? $platforms : (array) $platforms)
			->map(fn ($platform) => $this->canonicalIssuePlatformOption((string) $platform));

		return collect($this->preferredIssuePlatformOptions((string) $this->deliveryOrder->platform))
			->merge([$this->deliveryOrder->platform, $this->deliveryOrder->issue_platform])
			->merge((array) config('delivery.connection_platforms', []))
			->merge((array) config('delivery.direct_delivery_platforms', []))
			->merge($accountPlatforms)
			->filter()
			->map(fn ($platform) => $this->canonicalIssuePlatformOption((string) $platform))
			->unique()
			->values()
			->all();
	}

	public function getAvailableGamesProperty(): array
	{
		$platform = trim($this->issuePlatform) !== ''
			? trim($this->issuePlatform)
			: (string) $this->deliveryOrder->platform;
		$gameSearch = trim($this->game);

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

	public function getAvailableAccountsProperty(): array
	{
		$platform = trim($this->issuePlatform) !== ''
			? trim($this->issuePlatform)
			: (string) $this->deliveryOrder->platform;
		$game = trim($this->game);

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
			->get(['id', 'login', 'available_uses', 'platform'])
			->map(fn (Account $account): array => [
				'id' => (int) $account->id,
				'login' => (string) $account->login,
				'available_uses' => (int) $account->available_uses,
				'platforms' => collect(is_array($account->platform) ? $account->platform : [$account->platform])
					->filter()
					->map(fn ($platform) => $this->canonicalIssuePlatformOption((string) $platform))
					->unique()
					->values()
					->all(),
			])
			->all();
	}

	public function statusLabel(string $status): string
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

	public function statusVariant(string $status): string
	{
		return match ($status) {
			'connected', 'account_assigned' => 'green',
			'waiting_for_operator', 'waiting_for_connection_code', 'connection_code_submitted', 'operator_connecting' => 'blue',
			'connection_failed', 'locked_24h' => 'amber',
			'expired', 'cancelled' => 'red',
			default => 'gray',
		};
	}

	public function render()
	{
		return view('admin.delivery-orders.show', [
			'order' => $this->deliveryOrder,
			'items' => $this->deliveryOrder->items,
			'operators' => $this->operators,
			'issuePlatformOptions' => $this->issuePlatformOptions,
			'availableGames' => $this->availableGames,
			'availableAccounts' => $this->availableAccounts,
			'events' => DeliveryEvent::query()
				->where('delivery_order_id', $this->deliveryOrder->id)
				->latest('created_at')
				->limit(50)
				->get(),
		])->layout('layouts.admin');
	}

	private function refreshOrder(): void
	{
		$this->deliveryOrder->refresh();
		$this->deliveryOrder->load(['operator', 'account', 'issuance', 'items']);
	}

	private function defaultIssuePlatform(): string
	{
		foreach ($this->preferredIssuePlatformOptions((string) $this->deliveryOrder->platform) as $platform) {
			if ($this->hasAvailableAccountsForPlatform($platform)) {
				return $platform;
			}
		}

		return (string) $this->deliveryOrder->platform;
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

	private function hasAvailableAccountsForPlatform(string $platform): bool
	{
		return Account::query()
			->where('status', AccountStatus::ACTIVE)
			->where(function ($query) use ($platform): void {
				foreach ($this->issuePlatformCandidates($platform) as $index => $candidate) {
					$method = $index === 0 ? 'whereJsonContains' : 'orWhereJsonContains';
					$query->{$method}('platform', $candidate);
				}
			})
			->where(function ($query): void {
				$query->where('available_uses', '>', 0)
					->orWhere(function ($nested): void {
						$nested->whereNotNull('next_release_at')
							->where('next_release_at', '<=', now());
					});
			})
			->exists();
	}

	private function canonicalIssuePlatformOption(string $platform): string
	{
		return match ($this->normalizePlatform($platform)) {
			'2' => 'Nintendo Switch 2',
			default => $this->normalizePlatform($platform),
		};
	}

	private function selectedOperatorTelegramId(): ?int
	{
		$telegramId = (int) $this->operatorTelegramId;

		if ($telegramId <= 0) {
			return null;
		}

		return TelegramUser::query()
			->where('telegram_id', $telegramId)
			->where('is_active', true)
			->exists()
				? $telegramId
				: null;
	}

	private function withOperator(callable $callback): void
	{
		Gate::authorize('admin');

		$telegramId = $this->selectedOperatorTelegramId();
		if ($telegramId === null) {
			$this->flashError('Выберите активного Telegram оператора.');
			return;
		}

		$callback($telegramId);
	}

	private function flashResult(\App\Delivery\DTO\DeliveryActionResult $result, string $defaultMessage): void
	{
		if ($result->failed()) {
			$this->flashError($result->message() ?? 'Не удалось выполнить действие.');
			return;
		}

		session()->flash('message', $result->message() ?? $defaultMessage);
	}

	private function flashError(string $message): void
	{
		session()->flash('error', $message);
	}
}

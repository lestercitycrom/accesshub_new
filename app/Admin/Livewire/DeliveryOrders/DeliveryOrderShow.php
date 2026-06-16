<?php

declare(strict_types=1);

namespace App\Admin\Livewire\DeliveryOrders;

use App\Delivery\Models\DeliveryEvent;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class DeliveryOrderShow extends Component
{
	public DeliveryOrder $deliveryOrder;
	public string $operatorTelegramId = '';
	public string $game = '';
	public string $issuePlatform = '';
	public string $extraAttempts = '1';
	public string $failReason = '';

	public function mount(DeliveryOrder $deliveryOrder): void
	{
		Gate::authorize('admin');

		$this->deliveryOrder = $deliveryOrder;
		$this->refreshOrder();

		$this->game = (string) ($this->deliveryOrder->game ?? '');
		$this->issuePlatform = (string) ($this->deliveryOrder->issue_platform ?? $this->deliveryOrder->platform);
		$this->operatorTelegramId = (string) (
			$this->deliveryOrder->operator_telegram_id
			?? TelegramUser::query()->where('is_active', true)->orderBy('username')->value('telegram_id')
			?? ''
		);
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

		$result = $orders->assignAccount(
			$this->deliveryOrder,
			$telegramId,
			trim($this->game),
			trim($this->issuePlatform) !== '' ? trim($this->issuePlatform) : null,
		);

		$this->refreshOrder();

		if ($result->failed()) {
			$this->flashError($result->message() ?? 'Не удалось выдать аккаунт.');
			return;
		}

		session()->flash('message', $result->message() ?? 'Аккаунт выдан.');
	}

	public function markOperatorConnecting(DeliveryOrderService $orders): void
	{
		$this->withOperator(function (int $telegramId) use ($orders): void {
			$orders->markOperatorConnecting($this->deliveryOrder, $telegramId);
			$this->refreshOrder();
			session()->flash('message', 'Статус обновлен: оператор подключает.');
		});
	}

	public function markConnected(DeliveryOrderService $orders): void
	{
		$this->withOperator(function (int $telegramId) use ($orders): void {
			$orders->markConnected($this->deliveryOrder, $telegramId);
			$this->refreshOrder();
			session()->flash('message', 'Статус обновлен: подключение выполнено.');
		});
	}

	public function markConnectionFailed(DeliveryOrderService $orders): void
	{
		$this->withOperator(function (int $telegramId) use ($orders): void {
			$orders->markConnectionFailed(
				$this->deliveryOrder,
				$telegramId,
				trim($this->failReason) !== '' ? trim($this->failReason) : null,
			);

			$this->failReason = '';
			$this->refreshOrder();
			session()->flash('message', 'Статус обновлен: ошибка подключения.');
		});
	}

	public function grantExtraAttempts(DeliveryOrderService $orders): void
	{
		$this->withOperator(function (int $telegramId) use ($orders): void {
			$amount = max(1, (int) $this->extraAttempts);

			$orders->grantExtraAttempts($this->deliveryOrder, $telegramId, $amount);
			$this->refreshOrder();
			session()->flash('message', "Добавлено попыток: {$amount}.");
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
		return collect([$this->deliveryOrder->platform, $this->deliveryOrder->issue_platform])
			->merge((array) config('delivery.connection_platforms', []))
			->merge((array) config('delivery.direct_delivery_platforms', []))
			->filter()
			->unique()
			->values()
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
			'operators' => $this->operators,
			'issuePlatformOptions' => $this->issuePlatformOptions,
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
		$this->deliveryOrder->load(['operator', 'account', 'issuance']);
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

	private function flashError(string $message): void
	{
		session()->flash('error', $message);
	}
}

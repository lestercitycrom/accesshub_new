<?php

declare(strict_types=1);

namespace App\Admin\Livewire\DeliveryOrders;

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Models\DeliveryOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class DeliveryOrdersIndex extends Component
{
	use WithPagination;

	public string $q = '';
	public string $statusFilter = '';
	public string $platformFilter = '';
	public string $density = 'normal';
	public string $sortBy = 'created_at';
	public string $sortDirection = 'desc';

	public function mount(): void
	{
		Gate::authorize('admin');
	}

	public function updatingQ(): void
	{
		$this->resetPage();
	}

	public function updatingStatusFilter(): void
	{
		$this->resetPage();
	}

	public function updatingPlatformFilter(): void
	{
		$this->resetPage();
	}

	public function sort(string $field): void
	{
		if (!in_array($field, ['id', 'order_number', 'platform', 'status', 'created_at', 'updated_at'], true)) {
			return;
		}

		if ($this->sortBy === $field) {
			$this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
		} else {
			$this->sortBy = $field;
			$this->sortDirection = 'asc';
		}

		$this->resetPage();
	}

	public function clearFilters(): void
	{
		$this->q = '';
		$this->statusFilter = '';
		$this->platformFilter = '';
		$this->sortBy = 'created_at';
		$this->sortDirection = 'desc';
		$this->resetPage();
	}

	/**
	 * @return LengthAwarePaginator<DeliveryOrder>
	 */
	public function getRowsProperty(): LengthAwarePaginator
	{
		$query = DeliveryOrder::query()
			->with(['operator', 'account'])
			->when($this->q !== '', function ($query): void {
				$q = '%' . trim($this->q) . '%';

				$query->where(function ($query) use ($q): void {
					$query->where('order_number', 'like', $q)
						->orWhere('customer_email', 'like', $q)
						->orWhere('game', 'like', $q)
						->orWhere('display_login', 'like', $q)
						->orWhere('last_connection_code', 'like', $q);
				});
			})
			->when($this->statusFilter !== '', function ($query): void {
				$query->where('status', $this->statusFilter);
			})
			->when($this->platformFilter !== '', function ($query): void {
				$query->where('platform', $this->platformFilter);
			});

		$direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

		return $query
			->orderBy($this->sortBy, $direction)
			->paginate(20);
	}

	public function getStatusOptionsProperty(): array
	{
		return array_map(static fn (DeliveryOrderStatus $status): string => $status->value, DeliveryOrderStatus::cases());
	}

	public function getPlatformOptionsProperty(): array
	{
		return DeliveryOrder::query()
			->distinct()
			->pluck('platform')
			->merge((array) config('delivery.connection_platforms', []))
			->merge((array) config('delivery.direct_delivery_platforms', []))
			->filter()
			->unique()
			->sort()
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
		return view('admin.delivery-orders.index', [
			'rows' => $this->rows,
			'statusOptions' => $this->statusOptions,
			'platformOptions' => $this->platformOptions,
		])->layout('layouts.admin');
	}
}

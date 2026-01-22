<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Accounts;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class AccountLookup extends Component
{
	use WithPagination;

	public string $q = '';
	public string $statusFilter = '';
	public string $gameFilter = '';
	public string $platformFilter = '';
	public string $assignedFilter = '';
	public string $density = 'normal';

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

	public function updatingGameFilter(): void
	{
		$this->resetPage();
	}

	public function updatingPlatformFilter(): void
	{
		$this->resetPage();
	}

	public function updatingAssignedFilter(): void
	{
		$this->resetPage();
	}

	public function updatedDensity(): void
	{
		$this->resetPage();
	}

	/**
	 * @return LengthAwarePaginator<Account>
	 */
	public function getRowsProperty(): LengthAwarePaginator
	{
		return Account::query()
			->when($this->q !== '', function ($query): void {
				$q = '%' . $this->q . '%';

				// Search by login
				$query->where('login', 'like', $q)
					// Search by ID
					->orWhereRaw('CAST(id AS CHAR) LIKE ?', [$q])
					// Search by order_id via issuances
					->orWhereHas('issuances', function ($issuanceQuery) use ($q): void {
						$issuanceQuery->where('order_id', 'like', $q);
					});
			})
			->when($this->statusFilter !== '', function ($query): void {
				$query->where('status', $this->statusFilter);
			})
			->when($this->gameFilter !== '', function ($query): void {
				$query->where('game', $this->gameFilter);
			})
			->when($this->platformFilter !== '', function ($query): void {
				$query->where('platform', $this->platformFilter);
			})
			->when($this->assignedFilter !== '', function ($query): void {
				if ($this->assignedFilter === 'assigned') {
					$query->whereNotNull('assigned_to_telegram_id');
				} elseif ($this->assignedFilter === 'unassigned') {
					$query->whereNull('assigned_to_telegram_id');
				}
			})
			->orderByDesc('id')
			->paginate(20);
	}

	public function getStatusOptionsProperty(): array
	{
		return array_map(fn($status) => $status->value, AccountStatus::cases());
	}

	public function getGameOptionsProperty(): array
	{
		return Account::query()
			->distinct()
			->pluck('game')
			->filter()
			->sort()
			->values()
			->toArray();
	}

	public function getPlatformOptionsProperty(): array
	{
		return Account::query()
			->distinct()
			->pluck('platform')
			->filter()
			->sort()
			->values()
			->toArray();
	}

	public function render()
	{
		return view('admin.accounts.lookup', [
			'rows' => $this->rows,
			'statusOptions' => $this->statusOptions,
			'gameOptions' => $this->gameOptions,
			'platformOptions' => $this->platformOptions,
		])->layout('layouts.admin');
	}
}
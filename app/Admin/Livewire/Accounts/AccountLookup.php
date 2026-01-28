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
	public string $sortBy = 'id';
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

	public function sort(string $field): void
	{
		if ($this->sortBy === $field) {
			$this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
		} else {
			$this->sortBy = $field;
			$this->sortDirection = 'asc';
		}
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
				// Search in platform array using JSON contains
				$query->whereJsonContains('platform', $this->platformFilter);
			})
			->when($this->assignedFilter !== '', function ($query): void {
				if ($this->assignedFilter === 'assigned') {
					$query->whereNotNull('assigned_to_telegram_id');
				} elseif ($this->assignedFilter === 'unassigned') {
					$query->whereNull('assigned_to_telegram_id');
				}
			})
			->when($this->sortBy === 'platform', function ($query): void {
				// For JSON columns, we need special handling
				$direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
				$query->orderByRaw("JSON_EXTRACT(platform, '$[0]') {$direction}");
			})
			->when($this->sortBy !== 'platform', function ($query): void {
				$query->orderBy($this->sortBy, $this->sortDirection);
			})
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
		// Extract all platforms from JSON arrays
		$platforms = Account::query()
			->pluck('platform')
			->filter()
			->flatMap(function ($platform) {
				if (is_array($platform)) {
					return $platform;
				}
				// Try to decode JSON if it's a string
				$decoded = json_decode($platform, true);
				if (is_array($decoded)) {
					return $decoded;
				}
				return [$platform];
			})
			->unique()
			->sort()
			->values()
			->toArray();

		return $platforms;
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
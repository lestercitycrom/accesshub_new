<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Accounts;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class AccountsIndex extends Component
{
	use WithPagination;

	public string $q = '';
	public string $statusFilter = '';
	public string $gameFilter = '';
	public string $platformFilter = '';
	public array $selected = [];
	public string $density = 'normal';
	public ?string $alertMessage = null;
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

	public function updatedDensity(): void
	{
		$this->selected = [];
	}

	public function setStatus(string $status): void
	{
		Gate::authorize('admin');

		$validStatuses = array_map(fn($s) => $s->value, AccountStatus::cases());
		if (!in_array($status, $validStatuses, true)) {
			return;
		}

		Account::query()
			->whereIn('id', $this->selected)
			->update(['status' => $status]);

		$this->selected = [];
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

	public function clearFilters(): void
	{
		$this->q = '';
		$this->statusFilter = '';
		$this->gameFilter = '';
		$this->platformFilter = '';
		$this->alertMessage = null;
		$this->sortBy = 'id';
		$this->sortDirection = 'desc';
		$this->resetPage();
	}

	public function deleteAccount(int $accountId): void
	{
		Gate::authorize('admin');

		$account = Account::query()->find($accountId);
		if ($account === null) {
			return;
		}

		$account->delete();

		$this->selected = array_values(array_filter($this->selected, fn ($id) => (int) $id !== $accountId));
		$this->alertMessage = 'Аккаунт удалён.';
	}

	public function deleteAllAccounts(): void
	{
		Gate::authorize('admin');

		$count = Account::query()->count();
		Account::query()->delete();

		$this->alertMessage = "Удалено аккаунтов: {$count}.";
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

				$query->where(function ($subQuery) use ($q): void {
					$subQuery->where('login', 'like', $q)
						->orWhere('game', 'like', $q)
						// Search in platform JSON array
						->orWhereRaw('JSON_SEARCH(platform, "one", ?, NULL, "$[*]") IS NOT NULL', [$this->q]);
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
		$exportParams = array_filter([
			'q' => trim($this->q) !== '' ? $this->q : null,
			'status' => trim($this->statusFilter) !== '' ? $this->statusFilter : null,
			'game' => trim($this->gameFilter) !== '' ? $this->gameFilter : null,
			'platform' => trim($this->platformFilter) !== '' ? $this->platformFilter : null,
		], static fn ($v): bool => $v !== null);

		return view('admin.accounts.index', [
			'rows' => $this->rows,
			'statusOptions' => $this->statusOptions,
			'gameOptions' => $this->gameOptions,
			'platformOptions' => $this->platformOptions,
			'exportUrl' => route('admin.export.accounts.csv', $exportParams),
		])->layout('layouts.admin');
	}
}

<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Accounts;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Services\AccountDeletionService;
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

		app(AccountDeletionService::class)->deleteOne($accountId);

		$this->selected = array_values(array_filter($this->selected, fn ($id) => (int) $id !== $accountId));
		$this->alertMessage = 'Аккаунт удалён.';
	}

	/**
	 * @return LengthAwarePaginator<Account>
	 */
	public function getRowsProperty(): LengthAwarePaginator
	{
		return Account::query()
			->with('assignedOperator')
			->when($this->q !== '', function ($query): void {
				// Search by game name, console login or mail login (and by exact id
				// when the term is numeric). login/game are plain columns; the
				// encrypted password fields are intentionally not searchable.
				$term = trim($this->q);
				$query->where(function ($inner) use ($term): void {
					$like = '%' . $term . '%';
					$inner->where('game', 'like', $like)
						->orWhere('login', 'like', $like)
						->orWhere('mail_account_login', 'like', $like);
					if (is_numeric($term)) {
						$inner->orWhere('id', (int) $term);
					}
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

	/**
	 * @return \Illuminate\Support\Collection<int, Account>
	 */
	public function getCooldownAccountsProperty(): \Illuminate\Support\Collection
	{
		return Account::query()
			->where('status', AccountStatus::ACTIVE)
			->where('available_uses', 0)
			->whereNotNull('next_release_at')
			->where('next_release_at', '>', now())
			->orderBy('next_release_at')
			->get(['id', 'game', 'platform', 'login', 'next_release_at', 'max_uses']);
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
			'cooldownAccounts' => $this->cooldownAccounts,
		])->layout('layouts.admin');
	}
}

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

	/**
	 * @return LengthAwarePaginator<Account>
	 */
	public function getRowsProperty(): LengthAwarePaginator
	{
		return Account::query()
			->when($this->q !== '', function ($query): void {
				$q = '%' . $this->q . '%';

				$query->where('login', 'like', $q)
					->orWhere('game', 'like', $q)
					->orWhere('platform', 'like', $q);
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
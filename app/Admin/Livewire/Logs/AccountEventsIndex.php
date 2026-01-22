<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Logs;

use App\Domain\Accounts\Models\AccountEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class AccountEventsIndex extends Component
{
	use WithPagination;

	public string $accountId = '';
	public string $telegramId = '';
	public string $type = '';
	public string $dateFrom = '';
	public string $dateTo = '';

	public function mount(): void
	{
		Gate::authorize('admin');
	}

	public function updatingAccountId(): void { $this->resetPage(); }
	public function updatingTelegramId(): void { $this->resetPage(); }
	public function updatingType(): void { $this->resetPage(); }
	public function updatingDateFrom(): void { $this->resetPage(); }
	public function updatingDateTo(): void { $this->resetPage(); }

	public function clearFilters(): void
	{
		$this->accountId = '';
		$this->telegramId = '';
		$this->type = '';
		$this->dateFrom = '';
		$this->dateTo = '';
		$this->resetPage();
	}

	/**
	 * @return LengthAwarePaginator<AccountEvent>
	 */
	public function getRowsProperty(): LengthAwarePaginator
	{
		$query = AccountEvent::query();

		$accountId = trim($this->accountId);
		if ($accountId !== '' && ctype_digit($accountId)) {
			$query->where('account_id', (int) $accountId);
		}

		$telegramId = trim($this->telegramId);
		if ($telegramId !== '' && ctype_digit($telegramId)) {
			$query->where('telegram_id', (int) $telegramId);
		}

		$type = trim($this->type);
		if ($type !== '') {
			$query->where('type', 'like', '%' . $type . '%');
		}

		$dateFrom = trim($this->dateFrom);
		if ($dateFrom !== '') {
			$query->whereDate('created_at', '>=', $dateFrom);
		}

		$dateTo = trim($this->dateTo);
		if ($dateTo !== '') {
			$query->whereDate('created_at', '<=', $dateTo);
		}

		return $query->orderByDesc('created_at')->paginate(20);
	}

	public function render()
	{
		return view('admin.logs.account-events', [
			'rows' => $this->rows,
		])->layout('layouts.admin');
	}
}
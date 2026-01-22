<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Logs;

use App\Domain\Issuance\Models\Issuance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class IssuancesIndex extends Component
{
	use WithPagination;

	public string $orderId = '';
	public string $telegramId = '';
	public string $accountId = '';
	public string $game = '';
	public string $platform = '';
	public string $dateFrom = '';
	public string $dateTo = '';

	public function mount(): void
	{
		Gate::authorize('admin');
	}

	public function updatingOrderId(): void { $this->resetPage(); }
	public function updatingTelegramId(): void { $this->resetPage(); }
	public function updatingAccountId(): void { $this->resetPage(); }
	public function updatingGame(): void { $this->resetPage(); }
	public function updatingPlatform(): void { $this->resetPage(); }
	public function updatingDateFrom(): void { $this->resetPage(); }
	public function updatingDateTo(): void { $this->resetPage(); }

	public function clearFilters(): void
	{
		$this->orderId = '';
		$this->telegramId = '';
		$this->accountId = '';
		$this->game = '';
		$this->platform = '';
		$this->dateFrom = '';
		$this->dateTo = '';
		$this->resetPage();
	}

	/**
	 * @return LengthAwarePaginator<Issuance>
	 */
	public function getRowsProperty(): LengthAwarePaginator
	{
		$query = Issuance::query()->with(['account', 'telegramUser']);

		$orderId = trim($this->orderId);
		if ($orderId !== '') {
			$query->where('order_id', 'like', '%' . $orderId . '%');
		}

		$telegramId = trim($this->telegramId);
		if ($telegramId !== '' && ctype_digit($telegramId)) {
			$query->where('telegram_id', (int) $telegramId);
		}

		$accountId = trim($this->accountId);
		if ($accountId !== '' && ctype_digit($accountId)) {
			$query->where('account_id', (int) $accountId);
		}

		$game = trim($this->game);
		if ($game !== '') {
			$query->where('game', $game);
		}

		$platform = trim($this->platform);
		if ($platform !== '') {
			$query->where('platform', $platform);
		}

		$dateFrom = trim($this->dateFrom);
		if ($dateFrom !== '') {
			$query->whereDate('issued_at', '>=', $dateFrom);
		}

		$dateTo = trim($this->dateTo);
		if ($dateTo !== '') {
			$query->whereDate('issued_at', '<=', $dateTo);
		}

		return $query->orderByDesc('issued_at')->paginate(20);
	}

	public function render()
	{
		$exportParams = array_filter([
			'order_id' => trim($this->orderId) !== '' ? $this->orderId : null,
			'telegram_id' => trim($this->telegramId) !== '' ? $this->telegramId : null,
			'account_id' => trim($this->accountId) !== '' ? $this->accountId : null,
			'game' => trim($this->game) !== '' ? $this->game : null,
			'platform' => trim($this->platform) !== '' ? $this->platform : null,
			'date_from' => trim($this->dateFrom) !== '' ? $this->dateFrom : null,
			'date_to' => trim($this->dateTo) !== '' ? $this->dateTo : null,
		], static fn ($v): bool => $v !== null);

		return view('admin.logs.issuances', [
			'rows' => $this->rows,
			'exportUrl' => route('admin.export.issuances.csv', $exportParams),
		])->layout('layouts.admin');
	}
}
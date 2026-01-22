<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Problems;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Accounts\Services\AccountStatusService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class ProblemsIndex extends Component
{
	public string $tab = 'STOLEN';
	public string $q = '';
	public array $selected = [];
	public int $extendDays = 1;

	public function mount(): void
	{
		Gate::authorize('admin');
	}

	public function updatedTab(): void
	{
		$this->selected = [];
	}

	public function updatedQ(): void
	{
		$this->selected = [];
	}

	public function getTabsProperty(): array
	{
		return ['STOLEN', 'RECOVERY', 'TEMP_HOLD', 'DEAD', 'ALL'];
	}

	public function getRowsProperty()
	{
		$query = Account::query();

		// Filter by tab
		if ($this->tab !== 'ALL') {
			$query->where('status', $this->tab);
		} else {
			$query->whereIn('status', ['STOLEN', 'RECOVERY', 'TEMP_HOLD', 'DEAD']);
		}

		// Search by login
		if ($this->q !== '') {
			$query->where('login', 'like', '%' . $this->q . '%');
		}

		// Order by status priority then by deadline
		$query->orderByRaw("
			CASE
				WHEN status = 'STOLEN' THEN 1
				WHEN status = 'RECOVERY' THEN 2
				WHEN status = 'TEMP_HOLD' THEN 3
				WHEN status = 'DEAD' THEN 4
				ELSE 5
			END
		")
		->orderBy('status_deadline_at')
		->orderByDesc('updated_at');

		return $query->get();
	}

	public function releaseToPool(AccountStatusService $statusService): void
	{
		Gate::authorize('admin');

		if (empty($this->selected)) {
			return;
		}

		foreach ($this->selected as $accountId) {
			$statusService->releaseToPool($accountId, null);
		}

		$this->selected = [];
		$this->dispatch('refresh');
	}

	public function extendDeadline(AccountStatusService $statusService): void
	{
		Gate::authorize('admin');

		if (empty($this->selected) || $this->extendDays <= 0) {
			return;
		}

		foreach ($this->selected as $accountId) {
			$account = Account::find($accountId);
			if ($account && $account->status === AccountStatus::STOLEN) {
				$currentDeadline = $account->status_deadline_at ?? now();
				$account->status_deadline_at = $currentDeadline->addDays($this->extendDays);
				$account->save();

				AccountEvent::create([
					'account_id' => $account->id,
					'telegram_id' => null,
					'type' => 'EXTEND_DEADLINE',
					'payload' => [
						'days_added' => $this->extendDays,
						'new_deadline' => $account->status_deadline_at->toDateTimeString(),
					],
				]);
			}
		}

		$this->selected = [];
		$this->dispatch('refresh');
	}

	public function setStatus(string $status, AccountStatusService $statusService): void
	{
		Gate::authorize('admin');

		if (empty($this->selected)) {
			return;
		}

		$statusEnum = AccountStatus::from($status);

		foreach ($this->selected as $accountId) {
			$statusService->setStatus($accountId, $statusEnum, null);
		}

		$this->selected = [];
		$this->dispatch('refresh');
	}

	public function render()
	{
		return view('admin.problems.index', [
			'rows' => $this->rows,
			'tabs' => $this->tabs,
		])->layout('layouts.admin');
	}
}
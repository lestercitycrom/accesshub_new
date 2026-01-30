<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Accounts;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Services\AccountStatusService;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class AccountShow extends Component
{
	public Account $account;
	public string $setStatus = '';
	public ?string $assignToTelegramId = null;
	public string $newPassword = '';

	public function mount(Account $account): void
	{
		Gate::authorize('admin');

		$this->account = $account->load('assignedOperator');
	}

	public function applyStatus(AccountStatusService $statusService): void
	{
		Gate::authorize('admin');

		if ($this->setStatus === '') {
			return;
		}

		$status = AccountStatus::from($this->setStatus);
		$payload = [];
		if ($status === AccountStatus::STOLEN && $this->assignToTelegramId !== null && $this->assignToTelegramId !== '') {
			$tid = (int) $this->assignToTelegramId;
			if ($tid > 0) {
				$payload['assigned_to_telegram_id'] = $tid;
			}
		}
		$statusService->setStatus($this->account->id, $status, null, $payload);

		$this->account->refresh();
		$this->setStatus = '';
		$this->assignToTelegramId = null;

		session()->flash('message', 'Status updated successfully');
	}

	public function releaseToPool(AccountStatusService $statusService): void
	{
		Gate::authorize('admin');

		$statusService->releaseToPool($this->account->id, null);

		$this->account->refresh();

		session()->flash('message', 'Account released to pool');
	}

	public function updatePassword(AccountStatusService $statusService): void
	{
		Gate::authorize('admin');

		if (empty(trim($this->newPassword))) {
			return;
		}

		$statusService->adminUpdatePassword($this->account->id, $this->newPassword, null);

		$this->account->refresh();
		$this->newPassword = '';

		session()->flash('message', 'Password updated successfully');
	}

	public function getStatusOptionsProperty(): array
	{
		return array_map(fn($status) => $status->value, AccountStatus::cases());
	}

	public function getOperatorsProperty(): \Illuminate\Support\Collection
	{
		return TelegramUser::query()
			->where('is_active', true)
			->orderBy('username')
			->get();
	}

	public function render()
	{
		return view('admin.accounts.show', [
			'statuses' => $this->statusOptions,
			'operators' => $this->operators,
			'issuances' => $this->account->issuances()->with('telegramUser')->latest()->limit(20)->get(),
			'events' => $this->account->events()->with('telegramUser')->latest()->limit(50)->get(),
		])->layout('layouts.admin');
	}
}
<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Accounts;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class AccountForm extends Component
{
	public ?Account $account = null;

	public string $game = '';
	public string $platform = '';
	public string $login = '';
	public string $password = '';
	public string $status = 'ACTIVE';
	public ?string $assignedToTelegramId = null;
	public ?string $statusDeadlineAt = null;
	public bool $flagActionRequired = false;
	public bool $flagPasswordUpdateRequired = false;
	public ?string $metaEmailLogin = null;
	public ?string $metaEmailPassword = null;

	public function mount(?Account $account = null): void
	{
		Gate::authorize('admin');

		$this->account = $account;

		if ($account !== null) {
			$this->game = $account->game;
			$this->platform = $account->platform;
			$this->login = $account->login;
			$this->password = ''; // Don't show existing password
			$this->status = $account->status->value;
			$this->assignedToTelegramId = $account->assigned_to_telegram_id ? (string) $account->assigned_to_telegram_id : null;
			$this->statusDeadlineAt = $account->status_deadline_at?->format('Y-m-d\TH:i');

			// Initialize flags
			$this->flagActionRequired = ($account->flags['ACTION_REQUIRED'] ?? false) === true;
			$this->flagPasswordUpdateRequired = ($account->flags['PASSWORD_UPDATE_REQUIRED'] ?? false) === true;

			// Initialize meta
			$this->metaEmailLogin = $account->meta['email_login'] ?? null;
			$this->metaEmailPassword = $account->meta['email_password'] ?? null;
		}
	}

	public function save(): void
	{
		Gate::authorize('admin');

		$this->validate([
			'game' => ['required', 'string', 'max:255'],
			'platform' => ['required', 'string', 'max:255'],
			'login' => ['required', 'string', 'max:255'],
			'password' => ['required_if:account,null', 'string', 'min:1'],
			'status' => ['required', 'in:' . implode(',', array_map(fn($s) => $s->value, AccountStatus::cases()))],
			'assignedToTelegramId' => ['nullable', 'integer', 'min:1'],
			'statusDeadlineAt' => ['nullable', 'date'],
		]);

		// Build flags array
		$flags = [];
		if ($this->flagActionRequired) {
			$flags['ACTION_REQUIRED'] = true;
		}
		if ($this->flagPasswordUpdateRequired) {
			$flags['PASSWORD_UPDATE_REQUIRED'] = true;
		}

		// Build meta array
		$meta = [];
		if ($this->metaEmailLogin) {
			$meta['email_login'] = $this->metaEmailLogin;
		}
		if ($this->metaEmailPassword) {
			$meta['email_password'] = $this->metaEmailPassword;
		}

		$data = [
			'game' => $this->game,
			'platform' => $this->platform,
			'login' => $this->login,
			'status' => $this->status,
			'assigned_to_telegram_id' => $this->assignedToTelegramId ? (int) $this->assignedToTelegramId : null,
			'status_deadline_at' => $this->statusDeadlineAt ?: null,
			'flags' => !empty($flags) ? $flags : null,
			'meta' => !empty($meta) ? $meta : null,
		];

		if ($this->password !== '') {
			$data['password'] = $this->password;
		}

		if ($this->account !== null) {
			$this->account->update($data);
		} else {
			Account::query()->create($data);
		}

		redirect()->route('admin.accounts.index');
	}

	public function getStatusOptionsProperty(): array
	{
		return array_map(fn($status) => $status->value, AccountStatus::cases());
	}

	public function render()
	{
		return view('admin.accounts.form', [
			'isEdit' => $this->account !== null,
			'account' => $this->account,
			'statuses' => $this->statusOptions,
		])->layout('layouts.admin');
	}
}
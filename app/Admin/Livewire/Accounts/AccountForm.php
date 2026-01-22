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
	public ?string $flags = '';
	public ?string $meta = '';

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
			$this->flags = $account->flags ? json_encode($account->flags, JSON_PRETTY_PRINT) : '';
			$this->meta = $account->meta ? json_encode($account->meta, JSON_PRETTY_PRINT) : '';
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
			'flags' => ['nullable', 'json'],
			'meta' => ['nullable', 'json'],
		]);

		$data = [
			'game' => $this->game,
			'platform' => $this->platform,
			'login' => $this->login,
			'status' => $this->status,
			'assigned_to_telegram_id' => $this->assignedToTelegramId ? (int) $this->assignedToTelegramId : null,
			'flags' => $this->flags ? json_decode($this->flags, true) : null,
			'meta' => $this->meta ? json_decode($this->meta, true) : null,
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
			'statusOptions' => $this->statusOptions,
		])->layout('layouts.admin');
	}
}
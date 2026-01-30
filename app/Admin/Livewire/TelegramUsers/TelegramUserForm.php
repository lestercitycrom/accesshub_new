<?php

declare(strict_types=1);

namespace App\Admin\Livewire\TelegramUsers;

use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class TelegramUserForm extends Component
{
	public ?TelegramUser $telegramUser = null;

	public int $telegramId = 0;
	public ?string $username = null;
	public ?string $firstName = null;
	public ?string $lastName = null;
	public string $role = 'operator';
	public bool $isActive = true;

	public function mount(?TelegramUser $telegramUser = null): void
	{
		Gate::authorize('admin');

		$this->telegramUser = $telegramUser;

		if ($telegramUser !== null) {
			$this->telegramId = (int) $telegramUser->telegram_id;
			$this->username = $telegramUser->username;
			$this->firstName = $telegramUser->first_name;
			$this->lastName = $telegramUser->last_name;

			// Role can be null in DB or during initial hydration
			$this->role = $telegramUser->role?->value ?? TelegramRole::OPERATOR->value;

			$this->isActive = (bool) $telegramUser->is_active;
		}
	}

	public function save(): void
	{
		Gate::authorize('admin');

		$this->validate([
			'telegramId' => ['required', 'integer', 'min:1'],
			'role' => ['required', 'in:' . TelegramRole::OPERATOR->value . ',' . TelegramRole::ADMIN->value],
			'isActive' => ['boolean'],
		]);

		TelegramUser::query()->updateOrCreate(
			['telegram_id' => $this->telegramId],
			[
				'username' => $this->username,
				'first_name' => $this->firstName,
				'last_name' => $this->lastName,
				'role' => $this->role,
				'is_active' => $this->isActive,
			]
		);

		$this->redirect(route('admin.telegram-users.index'), navigate: true);
	}

	public function render()
	{
		return view('admin.telegram-users.form')->layout('layouts.admin');
	}
}

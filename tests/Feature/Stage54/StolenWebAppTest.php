<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Telegram\Models\TelegramUser;
use App\WebApp\Livewire\WebAppPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows operator to postpone stolen by one day', function (): void {
	$operator = TelegramUser::factory()->create(['telegram_id' => 111]);

	$account = Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'assigned_to_telegram_id' => $operator->telegram_id,
		'status_deadline_at' => now(),
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

	$oldDeadline = $account->status_deadline_at;

	Livewire::test(WebAppPage::class)
		->call('setTab', 'history')
		->call('postponeStolen', $account->id);

	$account->refresh();
	expect($account->status_deadline_at)->not->toEqual($oldDeadline);

	expect(AccountEvent::query()
		->where('account_id', $account->id)
		->where('type', 'EXTEND_DEADLINE')
		->exists())->toBeTrue();
});

it('allows operator to recover stolen via password form', function (): void {
	$operator = TelegramUser::factory()->create(['telegram_id' => 222]);

	$account = Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'assigned_to_telegram_id' => $operator->telegram_id,
		'status_deadline_at' => now(),
		'flags' => ['ACTION_REQUIRED' => true],
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

	Livewire::test(WebAppPage::class)
		->call('setTab', 'history')
		->call('openPasswordForm', $account->id, 'recover_stolen')
		->set('newPassword', 'new_stolen_pass')
		->call('submitPassword');

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::ACTIVE);
	expect($account->assigned_to_telegram_id)->toBeNull();
	expect($account->status_deadline_at)->toBeNull();
	expect($account->flags)->not->toHaveKey('ACTION_REQUIRED');

	expect(AccountEvent::query()
		->where('account_id', $account->id)
		->where('type', 'STOLEN_RECOVERED')
		->exists())->toBeTrue();
});

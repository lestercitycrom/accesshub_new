<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\Models\Issuance;
use App\WebApp\Livewire\WebAppPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('updates password via service and creates PASSWORD_UPDATED event', function (): void {
	$telegramUser = \App\Domain\Telegram\Models\TelegramUser::factory()->create(['telegram_id' => 111]);
	$telegramId = $telegramUser->telegram_id;

	$account = Account::factory()->create([
		'status' => AccountStatus::ACTIVE,
		'password' => 'old',
	]);

	Issuance::factory()->create([
		'telegram_id' => $telegramId,
		'account_id' => $account->id,
		'game' => 'cs2',
		'platform' => 'steam',
		'qty' => 1,
		'order_id' => 'ORD-3',
	]);

	$this->withSession(['webapp.telegram_id' => $telegramId]);

	Livewire::test(WebAppPage::class)
		->call('setTab', 'history')
		->call('openPasswordForm', $account->id)
		->set('newPassword', 'new-pass-123')
		->call('submitPassword');

	$account->refresh();

	expect($account->password)->toBe('new-pass-123');
	expect($account->status->value)->toBe(AccountStatus::ACTIVE->value);

	expect(AccountEvent::query()
		->where('account_id', $account->id)
		->where('telegram_id', $telegramId)
		->where('type', 'PASSWORD_UPDATED')
		->exists())->toBeTrue();
})->group('Stage53');
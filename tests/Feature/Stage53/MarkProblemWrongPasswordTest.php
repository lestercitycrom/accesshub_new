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

it('marks problem wrong_password and creates account event', function (): void {
	$telegramUser = \App\Domain\Telegram\Models\TelegramUser::factory()->create(['telegram_id' => 111]);
	$telegramId = $telegramUser->telegram_id;

	$account = Account::factory()->create([
		'status' => AccountStatus::ACTIVE,
	]);

	Issuance::factory()->create([
		'telegram_id' => $telegramId,
		'account_id' => $account->id,
		'game' => 'cs2',
		'platform' => 'steam',
		'qty' => 1,
		'order_id' => 'ORD-1',
	]);

	$this->withSession(['webapp.telegram_id' => $telegramId]);

	Livewire::test(WebAppPage::class)
		->call('setTab', 'history')
		->call('markProblem', $account->id, 'wrong_password');

	expect(AccountEvent::query()
		->where('account_id', $account->id)
		->where('telegram_id', $telegramId)
		->where('type', 'MARK_PROBLEM')
		->exists())->toBeTrue();
})->group('Stage53');
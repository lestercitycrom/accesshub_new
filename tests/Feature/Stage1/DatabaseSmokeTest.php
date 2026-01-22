<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates records via factories and respects casts', function (): void {
	$telegramUser = TelegramUser::factory()->admin()->create();

	expect($telegramUser->role)->toBe(TelegramRole::ADMIN);
	expect($telegramUser->is_active)->toBeTrue();

	$account = Account::factory()->status(AccountStatus::STOLEN)->create([
		'password' => 'plain-pass',
	]);

	// encrypted cast should return the original value
	expect($account->password)->toBe('plain-pass');
	expect($account->status)->toBe(AccountStatus::STOLEN);

	$issuance = Issuance::factory()->create([
		'telegram_id' => $telegramUser->telegram_id,
		'account_id' => $account->id,
		'qty' => 2,
	]);

	expect($issuance->telegram_id)->toBe($telegramUser->telegram_id);
	expect($issuance->account_id)->toBe($account->id);
	expect($issuance->qty)->toBe(2);

	$event = AccountEvent::factory()->create([
		'account_id' => $account->id,
		'telegram_id' => $telegramUser->telegram_id,
		'type' => 'ISSUED',
		'payload' => ['order_id' => 'ORD-1'],
	]);

	expect($event->payload)->toBe(['order_id' => 'ORD-1']);

	// Relations smoke
	expect($account->issuances()->count())->toBe(1);
	expect($account->events()->count())->toBe(1);
	expect($telegramUser->issuances()->count())->toBe(1);
});
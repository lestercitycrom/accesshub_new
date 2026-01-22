<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Services\AccountStatusService;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sets account status and creates event', function (): void {
	$account = Account::factory()->create(['status' => AccountStatus::ACTIVE]);
	$telegramUser = TelegramUser::factory()->create();

	$service = new AccountStatusService();
	$service->setStatus($account->id, AccountStatus::TEMP_HOLD, $telegramUser->telegram_id);

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::TEMP_HOLD);

	$event = $account->events()->first();
	expect($event)->not->toBeNull();
	expect($event->type)->toBe('SET_STATUS');
	expect($event->payload)->toHaveKey('status', 'TEMP_HOLD');
});

it('marks account as problem with wrong password reason', function (): void {
	$account = Account::factory()->create(['status' => AccountStatus::ACTIVE]);
	$telegramUser = TelegramUser::factory()->create();

	$service = new AccountStatusService();
	$service->markProblem($account->id, $telegramUser->telegram_id, 'wrong_password');

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::RECOVERY);
	expect($account->flags)->toHaveKey('PASSWORD_UPDATE_REQUIRED', true);

	$event = $account->events()->first();
	expect($event->type)->toBe('MARK_PROBLEM');
	expect($event->payload)->toHaveKey('new_status', 'RECOVERY');
});

it('marks account as stolen', function (): void {
	$account = Account::factory()->create(['status' => AccountStatus::ACTIVE]);
	$telegramUser = TelegramUser::factory()->create();

	$service = new AccountStatusService();
	$service->markProblem($account->id, $telegramUser->telegram_id, 'stolen');

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::STOLEN);
	expect($account->assigned_to_telegram_id)->toBe($telegramUser->telegram_id);
	expect($account->status_deadline_at)->not->toBeNull();
	expect($account->flags)->toHaveKey('ACTION_REQUIRED', true);
});

it('marks account as dead', function (): void {
	$account = Account::factory()->create(['status' => AccountStatus::ACTIVE]);
	$telegramUser = TelegramUser::factory()->create();

	$service = new AccountStatusService();
	$service->markProblem($account->id, $telegramUser->telegram_id, 'dead');

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::DEAD);
});

it('marks account as temp hold for unknown reason', function (): void {
	$account = Account::factory()->create(['status' => AccountStatus::ACTIVE]);
	$telegramUser = TelegramUser::factory()->create();

	$service = new AccountStatusService();
	$service->markProblem($account->id, $telegramUser->telegram_id, 'some_unknown_reason');

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::TEMP_HOLD);
});

it('updates password and sets status to active', function (): void {
	$account = Account::factory()->create([
		'status' => AccountStatus::RECOVERY,
		'flags' => ['PASSWORD_UPDATE_REQUIRED' => true]
	]);
	$telegramUser = TelegramUser::factory()->create();

	$service = new AccountStatusService();
	$service->updatePassword($account->id, 'new_password123', $telegramUser->telegram_id);

	$account->refresh();
	expect($account->password)->toBe('new_password123');
	expect($account->status)->toBe(AccountStatus::ACTIVE);
	expect($account->flags)->not->toHaveKey('PASSWORD_UPDATE_REQUIRED');

	$event = $account->events()->first();
	expect($event->type)->toBe('PASSWORD_UPDATED');
});

it('recovers stolen account', function (): void {
	$account = Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'assigned_to_telegram_id' => 12345,
		'status_deadline_at' => now()->addDays(5),
		'flags' => ['ACTION_REQUIRED' => true]
	]);
	$telegramUser = TelegramUser::factory()->create();

	$service = new AccountStatusService();
	$service->recoverStolen($account->id, 'recovered_password', $telegramUser->telegram_id);

	$account->refresh();
	expect($account->password)->toBe('recovered_password');
	expect($account->status)->toBe(AccountStatus::ACTIVE);
	expect($account->assigned_to_telegram_id)->toBeNull();
	expect($account->status_deadline_at)->toBeNull();
	expect($account->flags)->not->toHaveKey('ACTION_REQUIRED');

	$event = $account->events()->first();
	expect($event->type)->toBe('STOLEN_RECOVERED');
});
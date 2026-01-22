<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\DTO\IssuanceResult;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('successfully issues available account', function (): void {
	$telegramUser = TelegramUser::factory()->create();
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
	]);

	$service = new IssueService();
	$result = $service->issue(
		telegramId: $telegramUser->telegram_id,
		orderId: 'ORD-123',
		game: 'cs2',
		platform: 'steam',
		qty: 1
	);

	expect($result)->toBeInstanceOf(IssuanceResult::class);
	expect($result->success)->toBeTrue();
	expect($result->accountId)->toBe($account->id);
	expect($result->login)->toBe($account->login);
	expect($result->password)->toBe('secret123');
	expect($result->error)->toBeNull();
});

it('returns error when no available accounts', function (): void {
	$telegramUser = TelegramUser::factory()->create();

	$service = new IssueService();
	$result = $service->issue(
		telegramId: $telegramUser->telegram_id,
		orderId: 'ORD-123',
		game: 'cs2',
		platform: 'steam',
		qty: 1
	);

	expect($result->success)->toBeFalse();
	expect($result->error)->toBe('No available accounts');
});

it('applies cooldown when qty >= max_qty', function (): void {
	$telegramUser = TelegramUser::factory()->create();
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
	]);

	$service = new IssueService();
	$result = $service->issue(
		telegramId: $telegramUser->telegram_id,
		orderId: 'ORD-123',
		game: 'cs2',
		platform: 'steam',
		qty: 2 // >= max_qty (2)
	);

	expect($result->success)->toBeTrue();

	$account->refresh();
	$issuance = $account->issuances()->first();

	expect($issuance->cooldown_until)->not->toBeNull();
});

it('skips accounts with active cooldown', function (): void {
	$telegramUser = TelegramUser::factory()->create();

	// Create account and issuance with active cooldown
	$accountWithCooldown = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
	]);

	// Create issuance with future cooldown for this account
	Issuance::factory()->create([
		'account_id' => $accountWithCooldown->id,
		'telegram_id' => $telegramUser->telegram_id,
		'game' => 'cs2',
		'platform' => 'steam',
		'cooldown_until' => now()->addDays(1),
	]);

	// Create account without cooldown
	$accountWithoutCooldown = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
	]);

	$service = new IssueService();
	$result = $service->issue(
		telegramId: $telegramUser->telegram_id,
		orderId: 'ORD-123',
		game: 'cs2',
		platform: 'steam',
		qty: 1
	);

	expect($result->success)->toBeTrue();
	expect($result->accountId)->toBe($accountWithoutCooldown->id);
});

it('creates issuance and event records', function (): void {
	$telegramUser = TelegramUser::factory()->create();
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
	]);

	$service = new IssueService();
	$result = $service->issue(
		telegramId: $telegramUser->telegram_id,
		orderId: 'ORD-123',
		game: 'cs2',
		platform: 'steam',
		qty: 1
	);

	expect($result->success)->toBeTrue();

	$issuance = $account->issuances()->first();
	expect($issuance)->not->toBeNull();
	expect($issuance->telegram_id)->toBe($telegramUser->telegram_id);
	expect($issuance->order_id)->toBe('ORD-123');

	$event = $account->events()->first();
	expect($event)->not->toBeNull();
	expect($event->type)->toBe('ISSUED');
	expect($event->payload)->toHaveKey('order_id', 'ORD-123');
});
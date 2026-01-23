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

it('issuance result dto contract works', function (): void {
	// Test success factory
	$result = IssuanceResult::success(123, 'login123', 'pass123');
	expect($result->ok())->toBeTrue();
	expect($result->message())->toBeNull();
	expect($result->accountId)->toBe(123);
	expect($result->login)->toBe('login123');
	expect($result->password)->toBe('pass123');

	// Test fail factory
	$result = IssuanceResult::fail('Test error message');
	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('Test error message');
	expect($result->accountId)->toBeNull();

	// Test error factory (backward compatibility)
	$result = IssuanceResult::error('Another error message');
	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('Another error message');
})->group('Stage2');

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
	expect($result->ok())->toBeTrue();
	expect($result->accountId)->toBe($account->id);
	expect($result->login)->toBe($account->login);
	expect($result->password)->toBe('secret123');
	expect($result->message())->toBeNull();
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

	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('No available accounts.');
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

it('applies cooldown in operator_qty mode when qty >= max_qty', function (): void {
	config()->set('accesshub.issuance.cooldown_mode', 'operator_qty');
	config()->set('accesshub.issuance.operator_cooldown_days', 14);

	$telegramUser = TelegramUser::factory()->create();
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
	]);

	$service = new IssueService();

	// First issuance with qty >= 2 should apply cooldown
	$result1 = $service->issue($telegramUser->telegram_id, 'ORD-1', 'cs2', 'steam', 2);
	expect($result1->ok())->toBeTrue();

	$issuance1 = Issuance::query()->first();
	expect($issuance1->cooldown_until)->not->toBeNull();

	// Second issuance should be blocked by operator cooldown
	$result2 = $service->issue($telegramUser->telegram_id, 'ORD-2', 'cs2', 'steam', 1);
	expect($result2->ok())->toBeFalse();
	expect($result2->message())->toBe('Cooldown active. Try later.');
})->group('Stage2');

it('does not apply cooldown in operator_qty mode when qty < max_qty', function (): void {
	config()->set('accesshub.issuance.cooldown_mode', 'operator_qty');

	$telegramUser = TelegramUser::factory()->create();
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
	]);

	$service = new IssueService();

	// First issuance with qty = 1 should not apply cooldown
	$result1 = $service->issue($telegramUser->telegram_id, 'ORD-1', 'cs2', 'steam', 1);
	expect($result1->ok())->toBeTrue();

	$issuance1 = Issuance::query()->first();
	expect($issuance1->cooldown_until)->toBeNull();

	// Second issuance should work (no cooldown)
	$result2 = $service->issue($telegramUser->telegram_id, 'ORD-2', 'cs2', 'steam', 1);
	expect($result2->ok())->toBeTrue();
})->group('Stage2');

it('applies rolling 24h cooldown in rolling_24h mode', function (): void {
	config()->set('accesshub.issuance.cooldown_mode', 'rolling_24h');
	config()->set('accesshub.issuance.account_cooldown_hours', 24);

	$telegramUser = TelegramUser::factory()->create();
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
	]);

	$service = new IssueService();

	// First issuance should work
	$result1 = $service->issue($telegramUser->telegram_id, 'ORD-1', 'cs2', 'steam', 1);
	expect($result1->ok())->toBeTrue();

	// Second issuance should be blocked (account was issued recently)
	$result2 = $service->issue($telegramUser->telegram_id, 'ORD-2', 'cs2', 'steam', 1);
	expect($result2->ok())->toBeFalse();
	expect($result2->message())->toBe('No available accounts.');
})->group('Stage2');

it('applies both cooldown rules in both mode', function (): void {
	config()->set('accesshub.issuance.cooldown_mode', 'both');
	config()->set('accesshub.issuance.operator_cooldown_days', 14);
	config()->set('accesshub.issuance.account_cooldown_hours', 24);

	$telegramUser = TelegramUser::factory()->create();
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
	]);

	$service = new IssueService();

	// First issuance with qty >= 2 should apply cooldown
	$result1 = $service->issue($telegramUser->telegram_id, 'ORD-1', 'cs2', 'steam', 2);
	expect($result1->ok())->toBeTrue();

	$issuance1 = Issuance::query()->first();
	expect($issuance1->cooldown_until)->not->toBeNull();

	// Second issuance should be blocked by operator cooldown (first qty >= 2)
	$result2 = $service->issue($telegramUser->telegram_id, 'ORD-2', 'cs2', 'steam', 1);
	expect($result2->ok())->toBeFalse();
	expect($result2->message())->toBe('Cooldown active. Try later.');
})->group('Stage2');
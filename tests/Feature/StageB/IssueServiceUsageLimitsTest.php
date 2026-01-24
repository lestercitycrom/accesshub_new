<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('decrements available_uses and sets next_release_at when reaches zero', function (): void {
	config()->set('accesshub.issuance.cooldown_days', 14);

	$user = TelegramUser::factory()->create([
		'telegram_id' => 111,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);

	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 1,
		'available_uses' => 1,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue($user->telegram_id, 'ORD-1', 'cs2', 'steam', 1);

	expect($result->ok())->toBeTrue();
	expect(count($result->items))->toBe(1);

	$account->refresh();

	expect($account->available_uses)->toBe(0);
	expect($account->next_release_at)->not->toBeNull();
})->group('StageB');

it('restores availability to 1 when cooldown is reached', function (): void {
	config()->set('accesshub.issuance.cooldown_days', 14);

	$user = TelegramUser::factory()->create([
		'telegram_id' => 111,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);

	$past = CarbonImmutable::now()->subDay();

	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 0,
		'next_release_at' => $past,
	]);

	$service = app(IssueService::class);

	$result = $service->issue($user->telegram_id, 'ORD-2', 'cs2', 'steam', 1);

	expect($result->ok())->toBeTrue();

	$account->refresh();

	expect($account->available_uses)->toBe(0); // it was restored to 1 then decremented to 0
	expect($account->next_release_at)->not->toBeNull(); // set again for +14 days
})->group('StageB');

it('issues qty=2 atomically or fails without decrement', function (): void {
	config()->set('accesshub.issuance.max_qty', 2);

	$user = TelegramUser::factory()->create([
		'telegram_id' => 111,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);

	$onlyOne = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 1,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue($user->telegram_id, 'ORD-3', 'cs2', 'steam', 2);

	expect($result->ok())->toBeFalse();

	$onlyOne->refresh();
	expect($onlyOne->available_uses)->toBe(1);
})->group('StageB');

it('issues two distinct accounts when enough available', function (): void {
	config()->set('accesshub.issuance.max_qty', 2);

	$user = TelegramUser::factory()->create([
		'telegram_id' => 111,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);

	Account::factory()->count(2)->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 1,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue($user->telegram_id, 'ORD-4', 'cs2', 'steam', 2);

	expect($result->ok())->toBeTrue();
	expect(count($result->items))->toBe(2);

	expect($result->items[0]['account_id'])->not->toBe($result->items[1]['account_id']);
})->group('StageB');

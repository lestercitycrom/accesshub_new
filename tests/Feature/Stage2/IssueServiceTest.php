<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\DTO\IssuanceResult;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
	config()->set('accesshub.issuance.cooldown_days', 14);
	config()->set('accesshub.issuance.max_qty', 2);

	TelegramUser::factory()->create([
		'telegram_id' => 111,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
});

it('issuance result dto contract works (v3 items)', function (): void {
	$result = IssuanceResult::success([
		[
			'account_id' => 123,
			'login' => 'login123',
			'password' => 'pass123',
		],
	]);

	expect($result->ok())->toBeTrue();
	expect($result->message())->toBeNull();
	expect($result->items)->toBeArray();
	expect(count($result->items))->toBe(1);
	expect($result->items[0]['account_id'])->toBe(123);
	expect($result->items[0]['login'])->toBe('login123');
	expect($result->items[0]['password'])->toBe('pass123');

	$result = IssuanceResult::fail('Test error message');

	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('Test error message');
	expect($result->items)->toBeArray();
	expect(count($result->items))->toBe(0);
})->group('Stage2');

it('denies access when telegram user is missing', function (): void {
	$service = app(IssueService::class);

	$result = $service->issue(999, 'ORD-DENY-1', 'cs2', 'steam', 1);

	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('Доступ запрещен. Аккаунт на модерации или отключен.');
})->group('Stage2');

it('denies access when telegram user is inactive', function (): void {
	TelegramUser::query()->where('telegram_id', 111)->update(['is_active' => false]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-DENY-2', 'cs2', 'steam', 1);

	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('Доступ запрещен. Аккаунт на модерации или отключен.');
})->group('Stage2');

it('allows access for admin role', function (): void {
	TelegramUser::query()->where('telegram_id', 111)->update(['role' => TelegramRole::ADMIN]);

	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 1,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-ADMIN', 'cs2', 'steam', 1);

	expect($result->ok())->toBeTrue();
	expect(count($result->items))->toBe(1);
	expect((int) $result->items[0]['account_id'])->toBe($account->id);
})->group('Stage2');


it('enforces max qty limit', function (): void {
	config()->set('accesshub.issuance.max_qty', 2);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-QTY-LIMIT', 'cs2', 'steam', 3);

	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('Превышен лимит количества.');
})->group('Stage2');

it('normalizes qty to at least 1', function (): void {
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 3,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-QTY-NORM', 'cs2', 'steam', 0);

	expect($result->ok())->toBeTrue();
	expect(count($result->items))->toBe(1);
	expect($result->items[0]['account_id'])->toBe($account->id);
})->group('Stage2');

it('successfully issues available account and decrements available_uses', function (): void {
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 3,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-123', 'cs2', 'steam', 1);

	expect($result)->toBeInstanceOf(IssuanceResult::class);
	expect($result->ok())->toBeTrue();
	expect($result->message())->toBeNull();
	expect(count($result->items))->toBe(1);

	$item = $result->items[0];

	expect($item['account_id'])->toBe($account->id);
	expect($item['login'])->toBe($account->login);
	expect($item['password'])->toBe((string) $account->password);

	$account->refresh();

	expect($account->available_uses)->toBe(2);
	expect($account->next_release_at)->toBeNull();

	expect(Issuance::query()->where('order_id', 'ORD-123')->count())->toBe(1);

	expect(AccountEvent::query()
		->where('account_id', $account->id)
		->where('type', 'ISSUED')
		->exists()
	)->toBeTrue();
})->group('Stage2');

it('returns error when no available accounts', function (): void {
	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-NA', 'cs2', 'steam', 1);

	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('Нет аккаунтов для указанной игры и платформы.');
})->group('Stage2');

it('filters by game and platform', function (): void {
	Account::factory()->create([
		'game' => 'dota2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 3,
		'next_release_at' => null,
	]);

	$target = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 3,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-FILTER', 'cs2', 'steam', 1);

	expect($result->ok())->toBeTrue();
	expect($result->items[0]['account_id'])->toBe($target->id);
})->group('Stage2');

it('does not issue non-active accounts', function (): void {
	Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::DEAD,
		'max_uses' => 3,
		'available_uses' => 3,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-NONACTIVE', 'cs2', 'steam', 1);

	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('Нет аккаунтов для указанной игры и платформы.');
})->group('Stage2');

it('does not issue account when next_release_at is in the future and available_uses is zero', function (): void {
	$future = CarbonImmutable::now()->addDay();

	Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 0,
		'next_release_at' => $future,
	]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-FUTURE', 'cs2', 'steam', 1);

	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('Нет доступных аккаунтов сейчас. Попробуйте позже.');
})->group('Stage2');

it('sets next_release_at when available_uses reaches zero', function (): void {
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 1,
		'available_uses' => 1,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-LIMIT', 'cs2', 'steam', 1);

	expect($result->ok())->toBeTrue();

	$account->refresh();

	expect($account->available_uses)->toBe(0);
	expect($account->next_release_at)->not->toBeNull();
})->group('Stage2');

it('restores availability to 1 when cooldown is reached and issues again', function (): void {
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 0,
		'next_release_at' => CarbonImmutable::now()->subMinute(),
	]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-RESTORE', 'cs2', 'steam', 1);

	expect($result->ok())->toBeTrue();

	$account->refresh();

	// restored to 1, then decremented to 0, and next_release_at set again
	expect($account->available_uses)->toBe(0);
	expect($account->next_release_at)->not->toBeNull();
})->group('Stage2');

it('fails atomically for qty=2 if only one account is available', function (): void {
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 1,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-QTY2-FAIL', 'cs2', 'steam', 2);

	expect($result->ok())->toBeFalse();
	expect($result->message())->toBe('Недостаточно доступных аккаунтов. Уменьшите количество или попробуйте позже.');

	$account->refresh();

	expect($account->available_uses)->toBe(1);
	expect(Issuance::query()->where('order_id', 'ORD-QTY2-FAIL')->count())->toBe(0);
})->group('Stage2');

it('issues two distinct accounts for qty=2 when enough available', function (): void {
	$accounts = Account::factory()->count(2)->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 1,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-QTY2-OK', 'cs2', 'steam', 2);

	expect($result->ok())->toBeTrue();
	expect(count($result->items))->toBe(2);

	$id1 = (int) $result->items[0]['account_id'];
	$id2 = (int) $result->items[1]['account_id'];

	expect($id1)->not->toBe($id2);

	foreach ($accounts as $account) {
		$account->refresh();
		expect($account->available_uses)->toBe(0);
		expect($account->next_release_at)->not->toBeNull();
	}

	expect(Issuance::query()->where('order_id', 'ORD-QTY2-OK')->count())->toBe(2);
})->group('Stage2');

it('does not issue the same account twice for the same order_id (excludes already issued)', function (): void {
	$accounts = Account::factory()->count(2)->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'max_uses' => 3,
		'available_uses' => 2,
		'next_release_at' => null,
	]);

	$service = app(IssueService::class);

	$result1 = $service->issue(111, 'ORD-IDEMP', 'cs2', 'steam', 2);
	expect($result1->ok())->toBeTrue();
	expect(count($result1->items))->toBe(2);

	$issuedIds = array_map(static fn (array $item): int => (int) $item['account_id'], $result1->items);

	// With only two accounts in pool, second call must fail because already issued are excluded.
	$result2 = $service->issue(111, 'ORD-IDEMP', 'cs2', 'steam', 1);

	expect($result2->ok())->toBeFalse();
	expect($result2->message())->toBe(
		'По этому заказу уже выданы все доступные аккаунты. Используйте новый order_id или дождитесь пополнения.'
	);

	// Ensure no extra issuances were created
	expect(Issuance::query()->where('order_id', 'ORD-IDEMP')->count())->toBe(2);

	// Ensure the first issuance actually used both accounts
	expect(count(array_unique($issuedIds)))->toBe(2);
})->group('Stage2');

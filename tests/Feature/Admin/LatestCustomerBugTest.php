<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\DTO\IssuanceResult;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Settings\Models\Setting;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('blocks a second issue request for the same order while preserving another available account', function (): void {
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 2026081101,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	$accounts = Account::factory()->count(2)->create([
		'game' => 'Cyberpunk 2077',
		'platform' => ['Xbox Series X'],
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);

	$service = app(IssueService::class);
	$first = $service->issue($operator->telegram_id, '3610202', 'Cyberpunk 2077', 'Xbox Series X', 1, accountId: $accounts[0]->id);
	$second = $service->issue($operator->telegram_id, '3610202', 'Cyberpunk 2077', 'Xbox Series X', 1, accountId: $accounts[1]->id);

	expect($first->ok())->toBeTrue()
		->and($second->ok())->toBeFalse()
		->and($second->reason())->toBe(IssuanceResult::REASON_ALREADY_ISSUED)
		->and(Issuance::query()->where('order_id', '3610202')->count())->toBe(1)
		->and($accounts[1]->fresh()->available_uses)->toBe(1);
});

it('keeps the explicit replacement workflow available for an issued order', function (): void {
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 2026081102,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	$accounts = Account::factory()->count(2)->create([
		'game' => 'Replacement Game',
		'platform' => ['Steam'],
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);

	$service = app(IssueService::class);
	$first = $service->issue($operator->telegram_id, 'REPLACEMENT-ORDER', 'Replacement Game', 'Steam', 1, accountId: $accounts[0]->id);
	$replacement = $service->issue(
		telegramId: $operator->telegram_id,
		orderId: 'REPLACEMENT-ORDER',
		game: 'Replacement Game',
		platform: 'Steam',
		qty: 1,
		accountId: $accounts[1]->id,
		allowRepeatOrder: true,
	);

	expect($first->ok())->toBeTrue()
		->and($replacement->ok())->toBeTrue()
		->and(Issuance::query()->where('order_id', 'REPLACEMENT-ORDER')->count())->toBe(2);
});

it('allows the webapp to issue two different games for one order number', function (): void {
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 2026081301,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	$firstAccount = Account::factory()->create([
		'game' => 'First Ordered Game',
		'platform' => ['PS5'],
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);
	$secondAccount = Account::factory()->create([
		'game' => 'Second Ordered Game',
		'platform' => ['PS5'],
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);

	foreach ([
		['game' => 'First Ordered Game', 'account_id' => $firstAccount->id],
		['game' => 'Second Ordered Game', 'account_id' => $secondAccount->id],
	] as $request) {
		$this->withSession(['webapp.telegram_id' => $operator->telegram_id])
			->postJson('/webapp/api/issue', [
				'order_id' => 'TWO-GAMES-ONE-ORDER',
				'game' => $request['game'],
				'platform' => 'PS5',
				'qty' => 1,
				'account_id' => $request['account_id'],
			])
			->assertOk()
			->assertJsonPath('ok', true);
	}

	expect(Issuance::query()->where('order_id', 'TWO-GAMES-ONE-ORDER')->count())->toBe(2);
});

it('renders a scrollable searchable account selector in the webapp', function (): void {
	$this->get('/webapp')
		->assertOk()
		->assertSee('id="issueAccount" class="form-select searchable-select"', false)
		->assertSee("initSearchableSelect('issueAccount')", false);
});

it('records an issuance for a game name longer than the former database limit', function (): void {
	$game = 'Call of Duty Black Ops III Zombies Chronicles Edition';
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 202608120002,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	$account = Account::factory()->create([
		'game' => $game,
		'platform' => ['PS5'],
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);

	$result = app(IssueService::class)->issue(
		telegramId: $operator->telegram_id,
		orderId: 'LONG-GAME-NAME-ORDER',
		game: $game,
		platform: 'PS5',
		qty: 1,
		accountId: $account->id,
	);

	expect(mb_strlen($game))->toBeGreaterThan(50)
		->and($result->ok())->toBeTrue()
		->and(Issuance::query()->where('order_id', 'LONG-GAME-NAME-ORDER')->value('game'))->toBe($game);
});

it('notifies all other active staff about a browser issuance without credentials', function (): void {
	config()->set('services.telegram.bot_token', 'test');
	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$operator = TelegramUser::factory()->create([
		'telegram_id' => 2026081103,
		'username' => 'browser_operator',
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	$otherOperator = TelegramUser::factory()->create([
		'telegram_id' => 2026081104,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	$deliveryOperator = TelegramUser::factory()->create([
		'telegram_id' => 2026081105,
		'role' => TelegramRole::DELIVERY_OPERATOR,
		'is_active' => true,
	]);
	$admin = TelegramUser::factory()->create([
		'telegram_id' => 2026081106,
		'role' => TelegramRole::ADMIN,
		'is_active' => true,
	]);
	TelegramUser::factory()->create([
		'telegram_id' => 2026081107,
		'role' => TelegramRole::ADMIN,
		'is_active' => false,
	]);
	$accounts = Account::factory()->count(2)->create([
		'game' => 'Staff Notice Game',
		'platform' => ['Windows'],
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
		'source_label' => Account::SOURCE_ALLKEYSHOP,
		'comment' => 'private operator comment',
	]);
	Setting::query()->create([
		'key' => 'webapp_issue_delivery',
		'value' => ['v' => 'webapp'],
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id])
		->postJson('/webapp/api/issue', [
			'order_id' => 'WEBAPP-STAFF-NOTICE',
			'game' => 'Staff Notice Game',
			'platform' => 'Windows',
			'qty' => 1,
			'account_id' => $accounts[0]->id,
		])
		->assertOk()
		->assertJsonPath('ok', true)
		->assertJsonPath('sent_to_chat', false);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id])
		->postJson('/webapp/api/issue', [
			'order_id' => 'WEBAPP-STAFF-NOTICE',
			'game' => 'Staff Notice Game',
			'platform' => 'Windows',
			'qty' => 1,
			'account_id' => $accounts[1]->id,
		])
		->assertUnprocessable()
		->assertJsonPath('ok', false)
		->assertJsonPath('error', 'По этому номеру заказа аккаунт уже выдан. Повторная выдача запрещена; для замены используйте действие «Замена».');

	Http::assertSentCount(3);
	foreach ([$otherOperator, $deliveryOperator, $admin] as $recipient) {
		Http::assertSent(fn ($request): bool =>
			(string) $request['chat_id'] === (string) $recipient->telegram_id
			&& str_contains((string) $request['text'], 'Аккаунт выдан')
			&& str_contains((string) $request['text'], 'Staff Notice Game')
			&& str_contains((string) $request['text'], 'Windows')
			&& str_contains((string) $request['text'], 'WEBAPP-STAFF-NOTICE')
			&& str_contains((string) $request['text'], 'ALLKEYSHOP')
			&& str_contains((string) $request['text'], '@browser_operator')
			&& !str_contains((string) $request['text'], (string) $accounts[0]->login)
			&& !str_contains((string) $request['text'], (string) $accounts[0]->password)
			&& !str_contains((string) $request['text'], 'private operator comment')
		);
	}
	expect(Issuance::query()->where('order_id', 'WEBAPP-STAFF-NOTICE')->count())->toBe(1);
});

it('returns sparse catalog matches as a JSON list so Windows renders in the webapp', function (): void {
	Account::factory()->create([
		'game' => 'Windows Only Game',
		'platform' => ['Windows'],
		'status' => AccountStatus::ACTIVE,
	]);

	$this->getJson('/webapp/api/schema')
		->assertOk()
		->assertJsonPath('tabs.0.fields.2.options.0.value', 'Windows')
		->assertJsonCount(1, 'tabs.0.fields.2.options');
});

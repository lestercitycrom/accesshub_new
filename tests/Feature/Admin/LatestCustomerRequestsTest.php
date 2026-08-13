<?php

declare(strict_types=1);

use App\Admin\Livewire\Accounts\AccountForm;
use App\Admin\Livewire\DeliveryLinks\DeliveryLinksIndex;
use App\Delivery\Models\DeliveryLink;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Services\PlatformCatalog;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Settings\Models\Setting;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lets an operator generate and download delivery links but not delete batches', function (): void {
	$operator = User::factory()->operator()->create();
	$link = DeliveryLink::factory()->create(['batch' => 'operator-safe-batch']);

	Livewire::actingAs($operator)
		->test(DeliveryLinksIndex::class)
		->assertSee('CSV (unused)')
		->assertDontSee('Delete unused');

	$this->actingAs($operator)
		->get(route('admin.export.delivery-links.csv', ['batch' => 'operator-safe-batch']))
		->assertOk()
		->assertSee($link->code);

	Livewire::actingAs($operator)
		->test(DeliveryLinksIndex::class)
		->call('deleteUnused', 'operator-safe-batch')
		->assertForbidden();

	expect(DeliveryLink::query()->whereKey($link->id)->exists())->toBeTrue();
});

it('returns available issue accounts with AllKeyShop visible before issuance', function (): void {
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 202608102201,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	$tagged = Account::factory()->create([
		'game' => 'Customer Selection Game',
		'platform' => ['Steam'],
		'login' => 'tagged-before-issue@example.test',
		'source_label' => Account::SOURCE_ALLKEYSHOP,
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);
	$alreadyUsed = Account::factory()->create([
		'game' => 'Customer Selection Game',
		'platform' => ['Steam'],
		'login' => 'already-used@example.test',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);
	Issuance::factory()->create([
		'order_id' => 'CUSTOMER-SELECT-ORDER',
		'account_id' => $alreadyUsed->id,
		'telegram_id' => $operator->telegram_id,
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id])
		->getJson('/webapp/api/available-accounts?platform=Steam&game=' . urlencode('Customer Selection Game') . '&order_id=CUSTOMER-SELECT-ORDER')
		->assertOk()
		->assertJsonCount(1, 'items')
		->assertJsonPath('items.0.id', $tagged->id)
		->assertJsonPath('items.0.login', 'tagged-before-issue@example.test')
		->assertJsonPath('items.0.source_label', Account::SOURCE_ALLKEYSHOP);
});

it('issues the exact account selected in the main mini app and returns its label', function (): void {
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 202608102202,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	Account::factory()->create([
		'game' => 'Exact Selection Game',
		'platform' => ['Steam'],
		'login' => 'automatic-first@example.test',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 2,
	]);
	$selected = Account::factory()->create([
		'game' => 'Exact Selection Game',
		'platform' => ['Steam'],
		'login' => 'selected-allkeyshop@example.test',
		'source_label' => Account::SOURCE_ALLKEYSHOP,
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);
	Setting::query()->create([
		'key' => 'webapp_issue_delivery',
		'value' => ['v' => 'webapp'],
		'updated_by_user_id' => 1,
	]);

	$response = $this->withSession(['webapp.telegram_id' => $operator->telegram_id])
		->postJson('/webapp/api/issue', [
			'order_id' => 'EXACT-SELECTION-ORDER',
			'game' => 'Exact Selection Game',
			'platform' => 'Steam',
			'qty' => 1,
			'account_id' => $selected->id,
		]);

	$response->assertOk()
		->assertJsonPath('items.0.account_id', $selected->id)
		->assertJsonPath('items.0.login', 'selected-allkeyshop@example.test')
		->assertJsonPath('items.0.source_label', Account::SOURCE_ALLKEYSHOP);

	expect((string) $response->json('message'))->toContain('ALLKEYSHOP');
});

it('sends browser-issued credentials to Telegram with a copy button', function (): void {
	config()->set('services.telegram.bot_token', 'test');
	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$operator = TelegramUser::factory()->create([
		'telegram_id' => 202608120001,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	$account = Account::factory()->create([
		'game' => 'Copy Button Game',
		'platform' => ['Steam'],
		'login' => 'copy-button@example.test',
		'password' => 'copy-button-pass',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);
	Setting::query()->create([
		'key' => 'webapp_issue_delivery',
		'value' => ['v' => 'chat'],
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id])
		->postJson('/webapp/api/issue', [
			'order_id' => 'COPY-BUTTON-ORDER',
			'game' => 'Copy Button Game',
			'platform' => 'Steam',
			'qty' => 1,
			'account_id' => $account->id,
		])
		->assertOk()
		->assertJsonPath('sent_to_chat', true)
		->assertJsonPath('show_in_webapp', false);

	Http::assertSentCount(1);
	Http::assertSent(fn ($request): bool =>
		(string) $request['chat_id'] === (string) $operator->telegram_id
		&& str_contains((string) $request['text'], 'Login: <code>copy-button@example.test</code>')
		&& str_contains((string) $request['text'], 'Password: <code>copy-button-pass</code>')
		&& ($request['reply_markup']['inline_keyboard'][0][0]['text'] ?? null) === '📋 Copy credentials'
		&& ($request['reply_markup']['inline_keyboard'][0][0]['copy_text']['text'] ?? null)
			=== "Login: copy-button@example.test\nPassword: copy-button-pass"
	);
});

it('does not allow one selected account to masquerade as a quantity of two', function (): void {
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 202608102203,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	$account = Account::factory()->create([
		'game' => 'Quantity Guard Game',
		'platform' => ['Steam'],
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 2,
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id])
		->postJson('/webapp/api/issue', [
			'order_id' => 'QUANTITY-GUARD-ORDER',
			'game' => 'Quantity Guard Game',
			'platform' => 'Steam',
			'qty' => 2,
			'account_id' => $account->id,
		])
		->assertUnprocessable()
		->assertJsonPath('error', 'Для выбора конкретного аккаунта укажите количество 1.');

	expect(Issuance::query()->where('order_id', 'QUANTITY-GUARD-ORDER')->exists())->toBeFalse();
});

it('keeps the selected account available when the order field blurs into the issue button', function (): void {
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 202608102204,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id])
		->get('/webapp')
		->assertOk()
		->assertSee('let selectedIssueAccountId = 0;', false)
		->assertSee('loadIssueAccounts(true)', false);
});

it('uses only the customer platform catalog and canonicalizes production aliases', function (): void {
	$expected = [
		'Steam',
		'Epic Games',
		'Windows',
		'PS3',
		'PS4',
		'PS5',
		'Xbox One',
		'Xbox Series X',
		'Nintendo Switch 1',
		'Nintendo Switch 2',
		'Origin',
		'Battle.net',
		'GOG',
		'Другое',
	];

	expect(PlatformCatalog::OPTIONS)->toBe($expected)
		->and(config('accesshub.platforms'))->toBe($expected)
		->and(PlatformCatalog::canonicalize('Epic'))->toBe('Epic Games')
		->and(PlatformCatalog::canonicalize('PC'))->toBe('Windows')
		->and(PlatformCatalog::canonicalize('Xbox X'))->toBe('Xbox Series X')
		->and(PlatformCatalog::canonicalize('Xbox One/Xbox X'))->toBeNull()
		->and(PlatformCatalog::normalizeList(['Xbox One/Xbox X']))->toBe(['Xbox One', 'Xbox Series X'])
		->and(PlatformCatalog::normalizeList(['Nintendo Switch 1/2']))->toBe(['Nintendo Switch 1', 'Nintendo Switch 2'])
		->and(PlatformCatalog::normalizeList(['PS4/PS5']))->toBe(['PS4', 'PS5'])
		->and(PlatformCatalog::searchCandidates('xbox'))->toContain('Xbox One', 'Xbox Series X')
		->and(PlatformCatalog::normalizeList(['Unknown Console']))->toBeNull();

	Livewire::actingAs(User::factory()->manager()->create())
		->test(AccountForm::class, ['account' => null])
		->assertViewHas('platformOptions', PlatformCatalog::OPTIONS);
});

it('splits every combined platform through the production data migration', function (): void {
	$xboxCombined = Account::factory()->create(['platform' => ['Xbox One/Xbox X']]);
	$xboxSeries = Account::factory()->create(['platform' => ['Xbox X']]);
	$epic = Account::factory()->create(['platform' => ['Epic']]);
	$nintendo = Account::factory()->create(['platform' => ['Nintendo Switch 1/2']]);
	$playStation = Account::factory()->create(['platform' => ['PS4/PS5']]);

	$migration = require database_path('migrations/2026_08_11_000004_split_combined_account_platforms.php');
	$migration->up();

	expect($xboxCombined->fresh()->platform)->toBe(['Xbox One', 'Xbox Series X'])
		->and($xboxSeries->fresh()->platform)->toBe(['Xbox Series X'])
		->and($epic->fresh()->platform)->toBe(['Epic Games'])
		->and($nintendo->fresh()->platform)->toBe(['Nintendo Switch 1', 'Nintendo Switch 2'])
		->and($playStation->fresh()->platform)->toBe(['PS4', 'PS5']);
});

it('shows cooldown accounts on a separate tab with the AllKeyShop label', function (): void {
	$manager = User::factory()->manager()->create();
	$baseAccount = Account::factory()->create([
		'login' => 'base-tab-account@example.test',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);
	$cooldownAccount = Account::factory()->create([
		'login' => 'cooldown-allkeyshop@example.test',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 0,
		'next_release_at' => now()->addDays(7),
		'source_label' => Account::SOURCE_ALLKEYSHOP,
	]);

	$component = Livewire::actingAs($manager)
		->test(\App\Admin\Livewire\Accounts\AccountsIndex::class)
		->assertSet('section', 'base')
		->assertSee('База аккаунтов')
		->assertSee('Кулдаун')
		->assertSee('base-tab-account@example.test')
		->assertDontSee('cooldown-allkeyshop@example.test');

	$component->call('showSection', 'cooldown')
		->assertSet('section', 'cooldown')
		->assertSee('cooldown-allkeyshop@example.test')
		->assertSee('ALLKEYSHOP')
		->assertDontSee('base-tab-account@example.test');

	$component->call('showSection', 'base')
		->set('q', 'cooldown-allkeyshop@example.test')
		->assertSee('cooldown-allkeyshop@example.test')
		->assertSee('ALLKEYSHOP');

	expect($baseAccount->exists)->toBeTrue()
		->and($cooldownAccount->exists)->toBeTrue();
});

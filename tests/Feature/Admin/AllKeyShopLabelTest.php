<?php

declare(strict_types=1);

use App\Admin\Livewire\Accounts\AccountForm;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('stores and renders the AllKeyShop label from the account form', function (): void {
	$manager = User::factory()->manager()->create();

	Livewire::actingAs($manager)
		->test(AccountForm::class, ['account' => null])
		->assertSee('Канал продаж')
		->assertDontSee('Источник аккаунта')
		->assertSee('AllKeyShop')
		->set('game', 'AllKeyShop Test Game')
		->set('platformSelected', ['Steam'])
		->set('login', 'allkeyshop-form@example.test')
		->set('password', 'secret-value')
		->set('maxUses', 1)
		->set('availableUses', 1)
		->set('isAllKeyShop', true)
		->call('save')
		->assertHasNoErrors()
		->assertRedirect(route('admin.accounts.index'));

	$account = Account::query()->where('login', 'allkeyshop-form@example.test')->firstOrFail();

	expect($account->source_label)->toBe(Account::SOURCE_ALLKEYSHOP)
		->and($account->isAllKeyShop())->toBeTrue();

	$this->actingAs($manager)
		->get(route('admin.accounts.index'))
		->assertOk()
		->assertSee('ALLKEYSHOP');

	$this->actingAs($manager)
		->get(route('admin.accounts.show', $account))
		->assertOk()
		->assertSee('ALLKEYSHOP');
});

it('pre-populates and can remove the AllKeyShop label while editing', function (): void {
	$manager = User::factory()->manager()->create();
	$account = Account::factory()->create(['source_label' => Account::SOURCE_ALLKEYSHOP]);

	Livewire::actingAs($manager)
		->test(AccountForm::class, ['account' => $account])
		->assertSet('isAllKeyShop', true)
		->set('isAllKeyShop', false)
		->call('save')
		->assertHasNoErrors();

	expect($account->fresh()->source_label)->toBeNull();
});

it('does not change automatic issuance for an AllKeyShop account', function (): void {
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 2026081020,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);
	$account = Account::factory()->create([
		'game' => 'AllKeyShop Auto Game',
		'platform' => ['Steam'],
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
		'source_label' => Account::SOURCE_ALLKEYSHOP,
	]);

	$result = app(IssueService::class)->issue(
		$operator->telegram_id,
		'ALLKEYSHOP-AUTO-1',
		'AllKeyShop Auto Game',
		'Steam',
		1,
	);

	expect($result->ok())->toBeTrue()
		->and($result->items[0]['account_id'])->toBe($account->id);
});

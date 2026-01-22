<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Models\TelegramUser;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders webapp page', function (): void {
	Livewire::test(\App\WebApp\Livewire\WebAppPage::class)
		->assertOk();
});

it('shows not bootstrapped state initially', function (): void {
	Livewire::test(\App\WebApp\Livewire\WebAppPage::class)
		->assertSet('isBootstrapped', false);
});

it('shows bootstrapped state when session exists', function (): void {
	session(['webapp.telegram_id' => 123456789]);

	Livewire::test(\App\WebApp\Livewire\WebAppPage::class)
		->assertSet('isBootstrapped', true);
});

it('submits form successfully when bootstrapped', function (): void {
	// Setup test data
	$telegramUser = TelegramUser::factory()->create(['telegram_id' => 123456789]);
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'login' => 'testlogin',
		'password' => 'testpass',
	]);

	// Set session
	session(['webapp.telegram_id' => 123456789]);

	Livewire::test(\App\WebApp\Livewire\WebAppPage::class)
		->set('orderId', 'ORD-123')
		->set('game', 'cs2')
		->set('platform', 'steam')
		->set('qty', 1)
		->call('submit')
		->assertSet('resultText', "✅ Аккаунт выдан!\n\nЛогин: testlogin\nПароль: testpass")
		->assertSet('orderId', '') // Form reset
		->assertSet('game', '')
		->assertSet('platform', '')
		->assertSet('qty', 1);
});

it('shows error when not bootstrapped', function (): void {
	Livewire::test(\App\WebApp\Livewire\WebAppPage::class)
		->set('orderId', 'ORD-123')
		->set('game', 'cs2')
		->set('platform', 'steam')
		->set('qty', 1)
		->call('submit')
		->assertSet('resultText', 'Ошибка: сессия не инициализирована. Выполните bootstrap.');
});

it('loads history when bootstrapped', function (): void {
	$telegramUser = TelegramUser::factory()->create(['telegram_id' => 123456789]);
	$account = Account::factory()->create();

	// Create some issuances
	Issuance::factory()->create([
		'telegram_id' => 123456789,
		'account_id' => $account->id,
		'order_id' => 'ORD-1',
	]);

	Issuance::factory()->create([
		'telegram_id' => 123456789,
		'account_id' => $account->id,
		'order_id' => 'ORD-2',
	]);

	session(['webapp.telegram_id' => 123456789]);

	Livewire::test(\App\WebApp\Livewire\WebAppPage::class)
		->assertSet('history', function ($history) {
			return $history->count() === 2;
		});
});

it('validates form fields', function (): void {
	session(['webapp.telegram_id' => 123456789]);

	Livewire::test(\App\WebApp\Livewire\WebAppPage::class)
		->set('orderId', '')
		->set('game', 'invalid')
		->set('platform', 'invalid')
		->set('qty', 0)
		->call('submit')
		->assertHasErrors(['orderId', 'game', 'platform', 'qty']);
});
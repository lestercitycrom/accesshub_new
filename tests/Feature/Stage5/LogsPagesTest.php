<?php

declare(strict_types=1);

use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders issuances log for admin and filters by order_id', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$operator = TelegramUser::factory()->create(['telegram_id' => 111, 'is_active' => true]);
	$account = Account::factory()->create();

	Issuance::factory()->create([
		'telegram_id' => $operator->telegram_id,
		'account_id' => $account->id,
		'order_id' => 'ORD-AAA',
		'game' => 'cs2',
		'platform' => 'steam',
	]);

	Issuance::factory()->create([
		'telegram_id' => $operator->telegram_id,
		'account_id' => $account->id,
		'order_id' => 'ORD-BBB',
		'game' => 'cs2',
		'platform' => 'steam',
	]);

	Livewire::test(\App\Admin\Livewire\Logs\IssuancesIndex::class)
		->set('orderId', 'AAA')
		->assertSee('ORD-AAA')
		->assertDontSee('ORD-BBB');
})->group('Stage5.6');

it('renders account events log for admin and filters by type', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$account = Account::factory()->create();

	AccountEvent::factory()->create([
		'account_id' => $account->id,
		'telegram_id' => null,
		'type' => 'SET_STATUS',
		'payload' => ['status' => 'DEAD'],
	]);

	AccountEvent::factory()->create([
		'account_id' => $account->id,
		'telegram_id' => null,
		'type' => 'ACCOUNT_UPDATED',
		'payload' => ['source' => 'admin'],
	]);

	Livewire::test(\App\Admin\Livewire\Logs\AccountEventsIndex::class)
		->set('type', 'SET_STATUS')
		->assertSee('SET_STATUS')
		->assertDontSee('ACCOUNT_UPDATED');
})->group('Stage5.6');
<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lookup finds account by partial login', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	Account::factory()->create(['login' => 'alpha_login', 'status' => AccountStatus::ACTIVE]);
	Account::factory()->create(['login' => 'beta_login', 'status' => AccountStatus::ACTIVE]);

	Livewire::test(\App\Admin\Livewire\Accounts\AccountLookup::class)
		->set('q', 'alpha')
		->assertSee('alpha_login')
		->assertDontSee('beta_login');
})->group('Stage5.5');

it('lookup finds account by order_id via issuances', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$operator = TelegramUser::factory()->create(['telegram_id' => 111, 'is_active' => true]);

	$account = Account::factory()->create([
		'login' => 'order_target',
		'status' => AccountStatus::ACTIVE,
	]);

	// Platform is now array in Account, but string in Issuance
	$platform = is_array($account->platform) ? $account->platform[0] : $account->platform;
	
	Issuance::factory()->create([
		'telegram_id' => $operator->telegram_id,
		'account_id' => $account->id,
		'order_id' => 'ORD-X',
		'game' => $account->game,
		'platform' => $platform,
	]);

	Livewire::test(\App\Admin\Livewire\Accounts\AccountLookup::class)
		->set('q', 'ORD-X')
		->assertSee('order_target');
})->group('Stage5.5');

it('account show renders issuances and events', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$operator = TelegramUser::factory()->create(['telegram_id' => 111, 'is_active' => true, 'username' => 'op']);

	$account = Account::factory()->create([
		'login' => 'login_show',
		'status' => AccountStatus::ACTIVE,
		'password' => 'p1',
	]);

	// Platform is now array in Account, but string in Issuance
	$platform = is_array($account->platform) ? $account->platform[0] : $account->platform;
	
	Issuance::factory()->create([
		'telegram_id' => $operator->telegram_id,
		'account_id' => $account->id,
		'order_id' => 'ORD-1',
		'game' => $account->game,
		'platform' => $platform,
	]);

	AccountEvent::factory()->create([
		'account_id' => $account->id,
		'telegram_id' => $operator->telegram_id,
		'type' => 'ISSUED',
		'payload' => ['order_id' => 'ORD-1'],
	]);

	Livewire::test(\App\Admin\Livewire\Accounts\AccountShow::class, ['account' => $account])
		->assertSee('login_show')
		->assertSee('ORD-1')
		->assertSee('ISSUED');
})->group('Stage5.5');

it('account show can set status and writes SET_STATUS event', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$account = Account::factory()->create(['status' => AccountStatus::ACTIVE]);

	Livewire::test(\App\Admin\Livewire\Accounts\AccountShow::class, ['account' => $account])
		->set('setStatus', 'DEAD')
		->call('applyStatus');

	expect($account->refresh()->status)->toBe(AccountStatus::DEAD);
	expect(AccountEvent::query()->where('account_id', $account->id)->where('type', 'SET_STATUS')->exists())->toBeTrue();
})->group('Stage5.5');

it('account show can release to pool and writes RELEASE_TO_POOL', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$account = Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'assigned_to_telegram_id' => 999,
		'status_deadline_at' => now()->addDays(2),
		'flags' => ['ACTION_REQUIRED' => true],
	]);

	Livewire::test(\App\Admin\Livewire\Accounts\AccountShow::class, ['account' => $account])
		->call('releaseToPool');

	$account->refresh();

	expect($account->status)->toBe(AccountStatus::ACTIVE);
	expect($account->assigned_to_telegram_id)->toBeNull();
	expect($account->status_deadline_at)->toBeNull();
	expect($account->flags)->toBe([]);

	expect(AccountEvent::query()->where('account_id', $account->id)->where('type', 'RELEASE_TO_POOL')->exists())->toBeTrue();
})->group('Stage5.5');

it('account show can update password as admin and writes PASSWORD_UPDATED', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$account = Account::factory()->create([
		'password' => 'old',
		'status' => AccountStatus::RECOVERY,
		'flags' => ['PASSWORD_UPDATE_REQUIRED' => true],
		'assigned_to_telegram_id' => 123,
		'status_deadline_at' => now()->addDay(),
	]);

	Livewire::test(\App\Admin\Livewire\Accounts\AccountShow::class, ['account' => $account])
		->set('newPassword', 'new-pass')
		->call('updatePassword');

	$account->refresh();

	expect((string) $account->password)->toBe('new-pass');
	expect($account->status)->toBe(AccountStatus::ACTIVE);
	expect($account->assigned_to_telegram_id)->toBeNull();
	expect($account->status_deadline_at)->toBeNull();
	expect($account->flags)->toBe([]);

	expect(AccountEvent::query()->where('account_id', $account->id)->where('type', 'PASSWORD_UPDATED')->exists())->toBeTrue();
})->group('Stage5.5');
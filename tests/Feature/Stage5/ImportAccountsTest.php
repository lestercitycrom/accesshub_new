<?php

declare(strict_types=1);

use App\Admin\Livewire\Import\ImportAccounts;
use App\Domain\Accounts\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders import accounts page for admin', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	Livewire::test(ImportAccounts::class)
		->assertOk()
		->assertSee('Импорт аккаунтов');
})->group('Stage5.4');

it('parses valid csv and shows preview', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$csv = "game,platform,login,password\ncs2,steam,user1,pass1\ndota2,epic,user2,pass2";

	Livewire::test(ImportAccounts::class)
		->set('csvText', $csv)
		->call('parseCsv')
		->assertSet('showPreview', true)
		->assertCount('preview', 2);
})->group('Stage5.4');

it('shows error for invalid csv', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$csv = "game,platform\ncs2,steam";

	Livewire::test(ImportAccounts::class)
		->set('csvText', $csv)
		->call('parseCsv')
		->assertSet('showPreview', false)
		->assertCount('parseErrors', 1);
})->group('Stage5.4');

it('skips existing accounts during import', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	// Create existing account
	Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'login' => 'user1',
	]);

	$csv = "game,platform,login,password\ncs2,steam,user1,pass1\ndota2,epic,user2,pass2";

	Livewire::test(ImportAccounts::class)
		->set('csvText', $csv)
		->call('parseCsv')
		->call('applyImport');

	expect(Account::query()->where('login', 'user1')->count())->toBe(1);
	expect(Account::query()->where('login', 'user2')->count())->toBe(1);
})->group('Stage5.4');

it('imports new accounts successfully', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$csv = "game,platform,login,password\ncs2,steam,user1,pass1";

	Livewire::test(ImportAccounts::class)
		->set('csvText', $csv)
		->call('parseCsv')
		->call('applyImport');

	$account = Account::query()->where('login', 'user1')->first();

	expect($account)->not->toBeNull();
	expect($account->game)->toBe('cs2');
	expect($account->platform)->toBe('steam');
	expect((string) $account->password)->toBe('pass1');
})->group('Stage5.4');

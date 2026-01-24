<?php

declare(strict_types=1);

use App\Domain\Accounts\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders accounts index for admin', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);

	Account::factory()->count(3)->create();

	$this->actingAs($admin);

	Livewire::test(\App\Admin\Livewire\Accounts\AccountsIndex::class)
		->assertOk()
		->assertSee('Аккаунты');
})->group('Stage5.3');

it('can filter accounts by status', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$activeAccount = Account::factory()->create(['login' => 'active_user', 'status' => 'ACTIVE']);
	Account::factory()->create(['login' => 'temp_user', 'status' => 'TEMP_HOLD']);

	Livewire::test(\App\Admin\Livewire\Accounts\AccountsIndex::class)
		->set('statusFilter', 'ACTIVE')
		->assertSee('active_user')
		->assertDontSee('temp_user');
})->group('Stage5.3');

it('can update status of selected accounts', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$a1 = Account::factory()->create(['status' => 'ACTIVE']);
	$a2 = Account::factory()->create(['status' => 'ACTIVE']);

	Livewire::test(\App\Admin\Livewire\Accounts\AccountsIndex::class)
		->set('selected', [$a1->id, $a2->id])
		->call('setStatus', 'TEMP_HOLD');

	expect($a1->refresh()->status->value)->toBe('TEMP_HOLD');
	expect($a2->refresh()->status->value)->toBe('TEMP_HOLD');
})->group('Stage5.3');

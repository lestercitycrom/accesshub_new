<?php

declare(strict_types=1);

use App\Admin\Livewire\Accounts\AccountsIndex;
use App\Domain\Accounts\Models\Account;
use App\Models\User;
use Livewire\Livewire;

// The accounts search must match by game name, console login or mail login —
// not only by numeric id (customer feedback: searching by ID is useless).
it('searches accounts by game, login and mail login (not just id)', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$target = Account::factory()->create([
		'game' => 'Alan Wake 2',
		'login' => 'wakelogin2255',
		'mail_account_login' => 'wakemail@narod.ru',
	]);
	$other = Account::factory()->create([
		'game' => 'FIFA 23',
		'login' => 'fifaguy',
		'mail_account_login' => 'fifa@mail.ru',
	]);

	// Assert on logins — they only appear in result rows, not in the game
	// filter dropdown (which lists every game name).

	// by game
	Livewire::test(AccountsIndex::class)
		->set('q', 'Alan Wake')
		->assertSee('wakelogin2255')
		->assertDontSee('fifaguy');

	// by console login
	Livewire::test(AccountsIndex::class)
		->set('q', 'wakelogin')
		->assertSee('wakelogin2255')
		->assertDontSee('fifaguy');

	// by mail login
	Livewire::test(AccountsIndex::class)
		->set('q', 'wakemail')
		->assertSee('wakelogin2255')
		->assertDontSee('fifaguy');

	// by exact id still works
	Livewire::test(AccountsIndex::class)
		->set('q', (string) $target->id)
		->assertSee('wakelogin2255')
		->assertDontSee('fifaguy');
});

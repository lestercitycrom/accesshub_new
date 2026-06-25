<?php

declare(strict_types=1);

use App\Admin\Livewire\Accounts\AccountsIndex;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Models\Issuance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('admin can delete an account with issued history from accounts list', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$account = Account::factory()->create();

	Issuance::factory()->create(['account_id' => $account->id]);

	expect(Account::query()->count())->toBe(1);
	expect(Issuance::query()->count())->toBe(1);

	Livewire::actingAs($admin)
		->test(AccountsIndex::class)
		->call('deleteAccount', $account->id)
		->assertHasNoErrors();

	expect(Account::query()->count())->toBe(0);
	expect(Issuance::query()->count())->toBe(0);
})->group('Stage5.9');

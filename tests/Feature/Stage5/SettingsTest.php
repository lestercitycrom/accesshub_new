<?php

declare(strict_types=1);

use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Settings\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('forbids non-admin to open settings', function (): void {
	$user = User::factory()->create(['is_admin' => false]);

	$this->actingAs($user)->get('/admin/settings')->assertForbidden();
})->group('Stage5.9');

it('admin can delete all accounts with issued history', function (): void {
	$admin = User::factory()->create([
		'is_admin' => true,
		'password' => Hash::make('admin-pass'),
	]);
	$this->actingAs($admin);

	$account = Account::factory()->create();
	Issuance::factory()->create(['account_id' => $account->id]);
	Account::factory()->count(2)->create();

	expect(Account::query()->count())->toBe(3);
	expect(Issuance::query()->count())->toBe(1);

	Livewire::actingAs($admin)
		->test(\App\Admin\Livewire\Settings\SettingsIndex::class)
		->set('confirmPassword', 'admin-pass')
		->call('deleteAllAccounts')
		->assertHasNoErrors()
		->assertSet('successMessage', 'Все аккаунты удалены.');

	expect(Account::query()->count())->toBe(0);
	expect(Issuance::query()->count())->toBe(0);
})->group('Stage5.9');

it('admin can save settings into database', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	Livewire::test(\App\Admin\Livewire\Settings\SettingsIndex::class)
		->set('cooldownDays', 40)
		->set('stolenDefaultDeadlineDays', 7)
		->set('maxQty', 5)
		->call('save')
		->assertSee('Настройки сохранены.');

	$cooldown = Setting::query()->where('key', 'cooldown_days')->firstOrFail();
	expect($cooldown->value['v'])->toBe(40);

	$deadline = Setting::query()->where('key', 'stolen_default_deadline_days')->firstOrFail();
	expect($deadline->value['v'])->toBe(7);

	$maxQty = Setting::query()->where('key', 'max_qty')->firstOrFail();
	expect($maxQty->value['v'])->toBe(5);
})->group('Stage5.9');

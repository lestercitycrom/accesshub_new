<?php

declare(strict_types=1);

use App\Admin\Livewire\Accounts\AccountsIndex;
use App\Admin\Livewire\Users\UsersIndex;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('derives role from the legacy is_admin flag', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);

	expect($admin->fresh()->role)->toBe(UserRole::ADMIN);
});

it('keeps is_admin in sync when a role is assigned', function (): void {
	$manager = User::factory()->create(['role' => UserRole::MANAGER]);
	$operator = User::factory()->create(['role' => UserRole::OPERATOR]);
	$admin = User::factory()->create(['role' => UserRole::ADMIN]);

	expect((bool) $manager->fresh()->is_admin)->toBeFalse()
		->and((bool) $operator->fresh()->is_admin)->toBeFalse()
		->and((bool) $admin->fresh()->is_admin)->toBeTrue();
});

it('locks out users without a role (preserves old behaviour)', function (): void {
	$noRole = User::factory()->create(); // role null, is_admin false

	$this->actingAs($noRole)->get(route('admin.accounts.index'))->assertForbidden();
});

it('lets an admin reach every tier', function (): void {
	$admin = User::factory()->admin()->create();

	$this->actingAs($admin)->get(route('admin.accounts.index'))->assertOk();          // view
	$this->actingAs($admin)->get(route('admin.accounts.create'))->assertOk();         // supply
	$this->actingAs($admin)->get(route('admin.delivery-links.index'))->assertOk();    // supply
	$this->actingAs($admin)->get(route('admin.issuances.index'))->assertOk();         // supply: logs
	$this->actingAs($admin)->get(route('admin.export.accounts.csv'))->assertOk();     // manage: export
	$this->actingAs($admin)->get(route('admin.settings.index'))->assertOk();          // manage
	$this->actingAs($admin)->get(route('admin.users.index'))->assertOk();             // manage
});

it('lets a manager use supply workflows but not sensitive import/export or system', function (): void {
	$manager = User::factory()->manager()->create();

	$this->actingAs($manager)->get(route('admin.accounts.index'))->assertOk();          // view
	$this->actingAs($manager)->get(route('admin.delivery-orders.index'))->assertOk();   // view/fulfil
	$this->actingAs($manager)->get(route('admin.accounts.create'))->assertOk();         // supply: add accounts
	$this->actingAs($manager)->get(route('admin.account-lookup'))->assertOk();          // supply: search base
	$this->actingAs($manager)->get(route('admin.delivery-links.index'))->assertOk();    // supply: create links
	$this->actingAs($manager)->get(route('admin.delivery-instructions.index'))->assertOk(); // supply
	$this->actingAs($manager)->get(route('admin.issuances.index'))->assertOk();         // supply: logs
	$this->actingAs($manager)->get(route('admin.export.delivery-links.csv'))->assertOk(); // supply: marketplace links

	$this->actingAs($manager)->get(route('admin.export.accounts.csv'))->assertForbidden(); // admin only now
	$this->actingAs($manager)->get(route('admin.settings.index'))->assertForbidden();   // manage
	$this->actingAs($manager)->get(route('admin.users.index'))->assertForbidden();      // manage
	$this->actingAs($manager)->get(route('admin.telegram-users.index'))->assertForbidden();
});

it('limits an operator to fulfillment views plus account creation', function (): void {
	$operator = User::factory()->operator()->create();

	// Allowed: delivery orders, problems, /accounts (cooldown block only), create, and delivery links.
	$this->actingAs($operator)->get(route('admin.delivery-orders.index'))->assertOk();
	$this->actingAs($operator)->get(route('admin.problems.index'))->assertOk();
	$this->actingAs($operator)->get(route('admin.accounts.index'))->assertOk();
	$this->actingAs($operator)->get(route('admin.accounts.create'))->assertOk();
	$this->actingAs($operator)->get(route('admin.delivery-links.index'))->assertOk();
	$this->actingAs($operator)->get(route('admin.export.delivery-links.csv'))->assertOk();

	// Base search + logs are now hidden from operators.
	$this->actingAs($operator)->get(route('admin.account-lookup'))->assertForbidden();
	$this->actingAs($operator)->get(route('admin.issuances.index'))->assertForbidden();
	$this->actingAs($operator)->get(route('admin.events.index'))->assertForbidden();

	// Existing-account supply, import/export and system remain forbidden.
	$this->actingAs($operator)->get(route('admin.delivery-instructions.index'))->assertForbidden();
	$this->actingAs($operator)->get(route('admin.export.accounts.csv'))->assertForbidden();
	$this->actingAs($operator)->get(route('admin.settings.index'))->assertForbidden();
	$this->actingAs($operator)->get(route('admin.users.index'))->assertForbidden();
});

it('keeps sensitive exports admin-only while supply roles can export delivery links', function (): void {
	$admin = User::factory()->admin()->create();
	$manager = User::factory()->manager()->create();
	$operator = User::factory()->operator()->create();

	$this->actingAs($admin)->get(route('admin.export.accounts.csv'))->assertOk();
	$this->actingAs($admin)->get(route('admin.export.delivery-links.csv'))->assertOk();
	$this->actingAs($admin)->get(route('admin.export.issuances.csv'))->assertOk();

	$this->actingAs($manager)->get(route('admin.export.accounts.csv'))->assertForbidden();
	$this->actingAs($manager)->get(route('admin.export.delivery-links.csv'))->assertOk();
	$this->actingAs($manager)->get(route('admin.export.issuances.csv'))->assertForbidden();

	$this->actingAs($operator)->get(route('admin.export.accounts.csv'))->assertForbidden();
	$this->actingAs($operator)->get(route('admin.export.delivery-links.csv'))->assertOk();
	$this->actingAs($operator)->get(route('admin.export.issuances.csv'))->assertForbidden();
});

it('gates the account card (show) to supply roles', function (): void {
	$account = App\Domain\Accounts\Models\Account::factory()->create();

	$this->actingAs(User::factory()->admin()->create())->get(route('admin.accounts.show', $account))->assertOk();
	$this->actingAs(User::factory()->manager()->create())->get(route('admin.accounts.show', $account))->assertOk();
	$this->actingAs(User::factory()->operator()->create())->get(route('admin.accounts.show', $account))->assertForbidden();
});

it('shows an operator only the cooldown block on the accounts page', function (): void {
	Livewire::actingAs(User::factory()->operator()->create())
		->test(AccountsIndex::class)
		->assertSee('кулдауне')      // cooldown widget heading
		->assertSee('Создать')       // may add inventory
		->assertDontSee('Import CSV') // no import
		->assertDontSee('Экспорт CSV'); // no export
});

it('shows a manager the base + create but not import/export', function (): void {
	Livewire::actingAs(User::factory()->manager()->create())
		->test(AccountsIndex::class)
		->assertSee('Создать')        // supply: create button
		->assertDontSee('Import CSV')  // admin only
		->assertDontSee('Экспорт CSV');
});

it('shows an admin import and export on the accounts page', function (): void {
	Livewire::actingAs(User::factory()->admin()->create())
		->test(AccountsIndex::class)
		->assertSee('Import CSV')
		->assertSee('Экспорт CSV');
});

it('gives visible feedback when the users list is refreshed', function (): void {
	Livewire::actingAs(User::factory()->admin()->create())
		->test(UsersIndex::class)
		->call('refreshList')
		->assertHasNoErrors()
		->assertSee('Список обновлён');
});

it('exposes capability helpers per role', function (): void {
	expect(User::factory()->admin()->create()->canSupply())->toBeTrue()
		->and(User::factory()->manager()->create()->canSupply())->toBeTrue()
		->and(User::factory()->operator()->create()->canSupply())->toBeFalse()
		->and(User::factory()->operator()->create()->canCreateAccounts())->toBeTrue()
		->and(User::factory()->operator()->create()->canManageDeliveryLinks())->toBeTrue();
});

it('changes a user role and syncs the legacy flag via the users screen', function (): void {
	$admin = User::factory()->admin()->create();
	$target = User::factory()->admin()->create();

	Livewire::actingAs($admin)
		->test(UsersIndex::class)
		->call('setRole', $target->id, UserRole::MANAGER->value);

	expect($target->fresh()->role)->toBe(UserRole::MANAGER)
		->and((bool) $target->fresh()->is_admin)->toBeFalse();
});

it('can revoke access by setting an empty role', function (): void {
	$admin = User::factory()->admin()->create();
	$target = User::factory()->operator()->create();

	Livewire::actingAs($admin)
		->test(UsersIndex::class)
		->call('setRole', $target->id, '');

	expect($target->fresh()->role)->toBeNull();
	$this->actingAs($target->fresh())->get(route('admin.accounts.index'))->assertForbidden();
});

it('refuses to remove the last admin', function (): void {
	$admin = User::factory()->admin()->create();

	Livewire::actingAs($admin)
		->test(UsersIndex::class)
		->call('setRole', $admin->id, UserRole::OPERATOR->value)
		->assertHasNoErrors();

	expect($admin->fresh()->role)->toBe(UserRole::ADMIN);
});

it('forbids a non-admin from mounting the users screen', function (): void {
	$manager = User::factory()->manager()->create();

	Livewire::actingAs($manager)
		->test(UsersIndex::class)
		->assertForbidden();
});

it('creates a new web user with a role (password hashed, is_admin synced)', function (): void {
	$admin = User::factory()->admin()->create();

	Livewire::actingAs($admin)
		->test(UsersIndex::class)
		->set('newName', 'New Manager')
		->set('newEmail', 'newmanager@example.com')
		->set('newPassword', 'secret123')
		->set('newRole', UserRole::MANAGER->value)
		->call('createUser')
		->assertHasNoErrors();

	$user = User::query()->where('email', 'newmanager@example.com')->first();

	expect($user)->not->toBeNull()
		->and($user->role)->toBe(UserRole::MANAGER)
		->and((bool) $user->is_admin)->toBeFalse()
		->and(Hash::check('secret123', $user->password))->toBeTrue();
});

it('renders the create-user form when opened', function (): void {
	$admin = User::factory()->admin()->create();

	Livewire::actingAs($admin)
		->test(UsersIndex::class)
		->assertSee('Добавить пользователя')
		->call('toggleCreate')
		->assertSet('showCreate', true)
		->assertSee('Новый пользователь');
});

it('validates the new-user form (required, unique email, min password)', function (): void {
	$admin = User::factory()->admin()->create();
	User::factory()->create(['email' => 'taken@example.com']);

	Livewire::actingAs($admin)
		->test(UsersIndex::class)
		->set('newName', '')
		->set('newEmail', 'taken@example.com')
		->set('newPassword', 'short')
		->set('newRole', UserRole::OPERATOR->value)
		->call('createUser')
		->assertHasErrors(['newName', 'newEmail', 'newPassword']);

	expect(User::query()->where('email', 'taken@example.com')->count())->toBe(1);
});

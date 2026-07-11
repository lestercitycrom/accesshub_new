<?php

declare(strict_types=1);

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
	$this->actingAs($admin)->get(route('admin.settings.index'))->assertOk();          // manage
	$this->actingAs($admin)->get(route('admin.users.index'))->assertOk();             // manage
});

it('lets a manager view + supply but not system config', function (): void {
	$manager = User::factory()->manager()->create();

	$this->actingAs($manager)->get(route('admin.accounts.index'))->assertOk();          // view
	$this->actingAs($manager)->get(route('admin.delivery-orders.index'))->assertOk();   // view/fulfil
	$this->actingAs($manager)->get(route('admin.accounts.create'))->assertOk();         // supply: add accounts
	$this->actingAs($manager)->get(route('admin.delivery-links.index'))->assertOk();    // supply: create links
	$this->actingAs($manager)->get(route('admin.delivery-instructions.index'))->assertOk(); // supply
	$this->actingAs($manager)->get(route('admin.export.accounts.csv'))->assertOk();     // supply: export

	$this->actingAs($manager)->get(route('admin.settings.index'))->assertForbidden();   // manage
	$this->actingAs($manager)->get(route('admin.users.index'))->assertForbidden();      // manage
	$this->actingAs($manager)->get(route('admin.telegram-users.index'))->assertForbidden();
});

it('lets an operator fulfil orders but not supply or manage', function (): void {
	$operator = User::factory()->operator()->create();

	// Fulfillment + read (operator's job): orders, issuances log, problems, accounts view.
	$this->actingAs($operator)->get(route('admin.delivery-orders.index'))->assertOk();
	$this->actingAs($operator)->get(route('admin.issuances.index'))->assertOk();
	$this->actingAs($operator)->get(route('admin.problems.index'))->assertOk();
	$this->actingAs($operator)->get(route('admin.accounts.index'))->assertOk();

	// Supply is forbidden: adding accounts, creating links, instructions, export.
	$this->actingAs($operator)->get(route('admin.accounts.create'))->assertForbidden();
	$this->actingAs($operator)->get(route('admin.delivery-links.index'))->assertForbidden();
	$this->actingAs($operator)->get(route('admin.delivery-instructions.index'))->assertForbidden();
	$this->actingAs($operator)->get(route('admin.export.accounts.csv'))->assertForbidden();

	// System is forbidden.
	$this->actingAs($operator)->get(route('admin.settings.index'))->assertForbidden();
	$this->actingAs($operator)->get(route('admin.users.index'))->assertForbidden();
});

it('exposes capability helpers per role', function (): void {
	expect(User::factory()->admin()->create()->canSupply())->toBeTrue()
		->and(User::factory()->manager()->create()->canSupply())->toBeTrue()
		->and(User::factory()->operator()->create()->canSupply())->toBeFalse();
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

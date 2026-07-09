<?php

declare(strict_types=1);

use App\Admin\Livewire\Users\UsersIndex;
use App\Enums\UserRole;
use App\Models\User;
use Livewire\Livewire;

it('derives role from the legacy is_admin flag', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);

	expect($admin->fresh()->role)->toBe(UserRole::ADMIN);
});

it('keeps is_admin in sync when a role is assigned', function (): void {
	$operator = User::factory()->create(['role' => UserRole::OPERATOR]);
	$admin = User::factory()->create(['role' => UserRole::ADMIN]);

	expect((bool) $operator->fresh()->is_admin)->toBeFalse()
		->and((bool) $admin->fresh()->is_admin)->toBeTrue();
});

it('locks out users without a role (preserves old behaviour)', function (): void {
	$noRole = User::factory()->create(); // role null, is_admin false

	$this->actingAs($noRole)->get(route('admin.accounts.index'))->assertForbidden();
});

it('lets an admin reach every tier', function (): void {
	$admin = User::factory()->admin()->create();

	$this->actingAs($admin)->get(route('admin.accounts.index'))->assertOk();          // view
	$this->actingAs($admin)->get(route('admin.accounts.create'))->assertOk();         // operate
	$this->actingAs($admin)->get(route('admin.delivery-instructions.index'))->assertOk(); // operate
	$this->actingAs($admin)->get(route('admin.settings.index'))->assertOk();          // manage
	$this->actingAs($admin)->get(route('admin.users.index'))->assertOk();             // manage
});

it('lets an operator view and operate but not manage', function (): void {
	$operator = User::factory()->operator()->create();

	$this->actingAs($operator)->get(route('admin.accounts.index'))->assertOk();          // view
	$this->actingAs($operator)->get(route('admin.accounts.create'))->assertOk();         // operate
	$this->actingAs($operator)->get(route('admin.delivery-instructions.index'))->assertOk(); // operate

	$this->actingAs($operator)->get(route('admin.settings.index'))->assertForbidden();   // manage
	$this->actingAs($operator)->get(route('admin.users.index'))->assertForbidden();      // manage
	$this->actingAs($operator)->get(route('admin.telegram-users.index'))->assertForbidden();
});

it('lets a viewer view but not operate or manage', function (): void {
	$viewer = User::factory()->viewer()->create();

	$this->actingAs($viewer)->get(route('admin.accounts.index'))->assertOk();        // view
	$this->actingAs($viewer)->get(route('admin.delivery-orders.index'))->assertOk(); // view

	$this->actingAs($viewer)->get(route('admin.accounts.create'))->assertForbidden();          // operate
	$this->actingAs($viewer)->get(route('admin.delivery-instructions.index'))->assertForbidden(); // operate
	$this->actingAs($viewer)->get(route('admin.settings.index'))->assertForbidden();           // manage
	$this->actingAs($viewer)->get(route('admin.users.index'))->assertForbidden();              // manage
});

it('changes a user role and syncs the legacy flag via the users screen', function (): void {
	$admin = User::factory()->admin()->create();
	$target = User::factory()->admin()->create();

	Livewire::actingAs($admin)
		->test(UsersIndex::class)
		->call('setRole', $target->id, UserRole::OPERATOR->value);

	expect($target->fresh()->role)->toBe(UserRole::OPERATOR)
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

	// Still an admin — the guard blocked the self-demotion.
	expect($admin->fresh()->role)->toBe(UserRole::ADMIN);
});

it('forbids a non-admin from mounting the users screen', function (): void {
	$operator = User::factory()->operator()->create();

	Livewire::actingAs($operator)
		->test(UsersIndex::class)
		->assertForbidden();
});

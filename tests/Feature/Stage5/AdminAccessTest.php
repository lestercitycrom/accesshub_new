<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guest from admin', function (): void {
	$this->get('/admin')->assertRedirect('/login');
})->group('Stage5.1');

it('forbids non-admin user', function (): void {
	$user = User::factory()->create(['is_admin' => false]);

	$this->actingAs($user)->get('/admin')->assertForbidden();
})->group('Stage5.1');

it('allows admin user', function (): void {
	$user = User::factory()->create(['is_admin' => true]);

	$this->actingAs($user)
	->get('/admin')
	->assertRedirect('/admin/dashboard');

})->group('Stage5.1');
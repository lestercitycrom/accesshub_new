<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates test admin with different email', function (): void {
	// Создать админа с другим email
	$admin = User::factory()->create([
		'name' => 'Test Admin',
		'email' => 'testadmin@gmail.com',
		'password' => bcrypt('admin123'),
		'is_admin' => true,
		'email_verified_at' => now(),
	]);

	expect($admin->is_admin)->toBe(true);
	expect($admin->email_verified_at)->not->toBeNull();

	echo "Created test admin: ID={$admin->id}, email={$admin->email}, is_admin=" . ($admin->is_admin ? 'yes' : 'no') . PHP_EOL;
})->group('create-test-admin');
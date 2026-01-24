<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fixes admin user', function (): void {
	// Удалить всех пользователей
	User::query()->delete();

	// Создать правильного админа
	$admin = User::factory()->create([
		'name' => 'Administrator',
		'email' => 'admin@gmail.com',
		'password' => bcrypt('admin123'),
		'is_admin' => true,
		'email_verified_at' => now(),
	]);

	expect($admin->is_admin)->toBe(true);
	expect($admin->email_verified_at)->not->toBeNull();

	echo "Fixed admin: ID={$admin->id}, email={$admin->email}, is_admin=" . ($admin->is_admin ? 'yes' : 'no') . ", verified=" . ($admin->email_verified_at ? 'yes' : 'no') . PHP_EOL;
})->group('fix-admin');
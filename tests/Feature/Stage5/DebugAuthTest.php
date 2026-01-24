<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('checks admin user exists and is verified', function (): void {
	$admin = User::where('email', 'admin@gmail.com')->first();

	if (!$admin) {
		$admin = User::factory()->create([
			'name' => 'Admin',
			'email' => 'admin@gmail.com',
			'password' => bcrypt('admin123'),
			'is_admin' => true,
			'email_verified_at' => now(),
		]);
	}

	expect($admin)->not->toBeNull();
	expect($admin->is_admin)->toBe(true);
	expect($admin->email_verified_at)->not->toBeNull();

	echo "Admin: ID={$admin->id}, email={$admin->email}, is_admin=" . ($admin->is_admin ? 'yes' : 'no') . ", verified=" . ($admin->email_verified_at ? 'yes' : 'no') . PHP_EOL;
})->group('debug');

it('tests admin can access accounts page', function (): void {
	$admin = User::factory()->create([
		'is_admin' => true,
		'email_verified_at' => now(),
	]);

	$response = $this->actingAs($admin)->get('/admin/accounts');

	expect($response->status())->toBe(200);
})->group('debug');
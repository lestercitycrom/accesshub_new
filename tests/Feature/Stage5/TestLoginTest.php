<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('login page renders', function (): void {
	// Создать админа
	$admin = User::factory()->create([
		'email' => 'testadmin@gmail.com',
		'password' => bcrypt('admin123'),
		'is_admin' => true,
		'email_verified_at' => now(),
	]);

	// Проверить что страница рендерится
	$response = $this->get('/login');
	expect($response->status())->toBe(200);

	// Проверить что есть поле email
	$response->assertSee('email');
})->group('test-login');

it('can login as admin', function (): void {
	$admin = User::factory()->create([
		'email' => 'testadmin@gmail.com',
		'password' => bcrypt('admin123'),
		'is_admin' => true,
		'email_verified_at' => now(),
	]);

	// Попытаться залогиниться через Livewire
	Livewire::test(\App\Livewire\Auth\Login::class)
		->set('email', 'testadmin@gmail.com')
		->set('password', 'admin123')
		->call('authenticate')
		->assertRedirect('/admin/accounts');

	// Проверить что залогинены
	expect(auth()->check())->toBe(true);
	expect((bool) auth()->user()->is_admin)->toBe(true);
})->group('test-login');
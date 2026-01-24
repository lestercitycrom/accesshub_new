<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create admin user', function (): void {
    $admin = User::factory()->create([
        'email' => 'admin@gmail.com',
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);

    expect($admin->is_admin)->toBe(true);
    expect($admin->email_verified_at)->not->toBeNull();
})->group('Stage5.simple');

it('admin routes exist', function (): void {
    $routes = app('router')->getRoutes();

    $adminRoutes = [];
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'admin')) {
            $adminRoutes[] = $route->uri();
        }
    }

    expect(count($adminRoutes))->toBeGreaterThan(0);
    expect($adminRoutes)->toContain('admin');
})->group('Stage5.simple');
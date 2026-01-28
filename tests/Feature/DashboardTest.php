<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('home'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create([
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    $response = $this->get(route('home'));
    $response->assertRedirect(route('admin.accounts.index'));
});

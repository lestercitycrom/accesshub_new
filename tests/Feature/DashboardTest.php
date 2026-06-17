<?php

use App\Models\User;

test('guests landing on root are sent to the public delivery form', function () {
    $response = $this->get(route('home'));
    $response->assertRedirect(route('delivery.take-order'));
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

<?php

test('registration screen can be rendered', function () {
    $this->markTestSkipped('Registration routes are not enabled in this application.');
    
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $this->markTestSkipped('Registration routes are not enabled in this application.');
    
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
});

<?php

test('the home route sends guests to the login page', function () {
    $response = $this->get(route('home'));

    // Guests are sent to the panel login (not the public take-order form) —
    // operators expect the admin panel at "/".
    $response->assertRedirect(route('login'));
});

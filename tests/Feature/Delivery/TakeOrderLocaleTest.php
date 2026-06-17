<?php

declare(strict_types=1);

// Public client site is English-facing even though the app locale is 'ru'.
// StoreOrderController forces 'en' so validation errors are English (not Russian).
it('shows english validation messages on the public take-order form', function (): void {
	$response = $this->from(route('delivery.take-order'))
		->post(route('delivery.take-order.store'), []);

	$response->assertSessionHasErrors(['order_number', 'email', 'platform']);

	$errors = session('errors');
	expect($errors->first('order_number'))->toContain('required')
		->and($errors->first('order_number'))->not->toContain('обязательно')
		->and($errors->first('email'))->not->toContain('обязательно')
		->and($errors->first('platform'))->not->toContain('обязательно');
});

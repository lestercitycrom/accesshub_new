<?php

declare(strict_types=1);

use App\Delivery\Models\DeliveryOrder;

it('renders take order page', function (): void {
	$this->get(route('delivery.take-order'))
		->assertOk()
		->assertSee('Get Game')
		->assertSee('Xbox');
});

it('stores a public delivery order and redirects to token page', function (): void {
	$response = $this->post(route('delivery.take-order.store'), [
		'order_number' => 'ORD-ROUTE',
		'email' => 'client@example.com',
		'platform' => 'Xbox',
	]);

	$order = DeliveryOrder::query()->where('order_number', 'ORD-ROUTE')->first();

	expect($order)->not->toBeNull();
	$response->assertRedirect(route('delivery.order.show', ['token' => $order->token]));
});

it('returns public order status payload', function (): void {
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-STATUS',
		'customer_email' => 'client@example.com',
		'platform' => 'Xbox',
	]);

	$this->getJson(route('delivery.order.status', ['token' => $order->token]))
		->assertOk()
		->assertJsonPath('order_number', 'ORD-STATUS')
		->assertJsonPath('customer_email', 'cl****@example.com')
		->assertJsonPath('connection.required', true);
});


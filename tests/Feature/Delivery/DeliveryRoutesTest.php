<?php

declare(strict_types=1);

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Models\DeliveryOrder;
use App\Domain\Accounts\Models\Account;

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

it('returns normalized json when connection code format is invalid', function (): void {
	$account = Account::factory()->create(['platform' => ['Xbox']]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-BAD-CODE',
		'customer_email' => 'client@example.com',
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE,
		'account_id' => $account->id,
		'display_login' => 'xbox-login',
		'display_password' => 'QR-1234',
		'display_password_type' => 'fake',
	]);

	$this->postJson(route('delivery.order.connection-code.store', ['token' => $order->token]), [
		'connection_code' => 'bad',
	])
		->assertUnprocessable()
		->assertJsonPath('ok', false)
		->assertJsonPath('message', 'Connection code must contain 6-8 letters or digits.')
		->assertJsonPath('order.order_number', 'ORD-BAD-CODE')
		->assertJsonPath('order.connection.attempts_used', 0);
});

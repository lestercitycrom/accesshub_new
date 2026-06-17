<?php

declare(strict_types=1);

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Models\DeliveryOrder;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Facades\Http;

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

it('notifies active telegram operators when a public delivery order is created', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	TelegramUser::factory()->create(['telegram_id' => 1001, 'is_active' => true]);
	TelegramUser::factory()->admin()->create(['telegram_id' => 1002, 'is_active' => true]);
	TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 1004, 'is_active' => true]);
	TelegramUser::factory()->inactive()->create(['telegram_id' => 1003]);

	Http::fake([
		'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$this->post(route('delivery.take-order.store'), [
		'order_number' => 'ORD-NOTIFY',
		'email' => 'client@example.com',
		'platform' => 'Xbox',
	])->assertRedirect();

	Http::assertSentCount(3);
	Http::assertSent(function ($request): bool {
		return $request['chat_id'] === '1001'
			&& str_contains($request['text'], 'New delivery order')
			&& str_contains($request['text'], 'ORD-NOTIFY')
			&& $request['reply_markup']['inline_keyboard'][0][0]['text'] === 'Open order'
			&& isset($request['reply_markup']['inline_keyboard'][0][0]['web_app']['url'])
			&& str_contains($request['reply_markup']['inline_keyboard'][0][0]['web_app']['url'], 'tab=delivery');
	});
	Http::assertSent(function ($request): bool {
		return $request['chat_id'] === '1002'
			&& str_contains($request['text'], 'New delivery order');
	});
	Http::assertSent(function ($request): bool {
		return $request['chat_id'] === '1004'
			&& str_contains($request['text'], 'New delivery order')
			&& isset($request['reply_markup']['inline_keyboard'][0][0]['web_app']['url']);
	});
});

it('stores a public delivery order when telegram notification fails', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	TelegramUser::factory()->create(['telegram_id' => 1101, 'is_active' => true]);

	Http::fake(function (): never {
		throw new RuntimeException('Telegram is unavailable.');
	});

	$response = $this->post(route('delivery.take-order.store'), [
		'order_number' => 'ORD-NOTIFY-FAIL',
		'email' => 'client@example.com',
		'platform' => 'Xbox',
	]);

	$order = DeliveryOrder::query()->where('order_number', 'ORD-NOTIFY-FAIL')->first();

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

it('accepts connection code submission when the public csrf session has expired', function (): void {
	$this->withMiddleware();

	$account = Account::factory()->create(['platform' => ['Xbox']]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-NO-CSRF',
		'customer_email' => 'client@example.com',
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE,
		'account_id' => $account->id,
		'display_login' => 'xbox-login',
		'display_password' => 'QR-1234',
		'display_password_type' => 'fake',
	]);

	$this->postJson(route('delivery.order.connection-code.store', ['token' => $order->token]), [
		'connection_code' => 'AB12CD',
	])
		->assertOk()
		->assertJsonPath('ok', true)
		->assertJsonPath('order.order_number', 'ORD-NO-CSRF')
		->assertJsonPath('order.connection.attempts_used', 1);
});

it('notifies active telegram operators when connection code is submitted', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	TelegramUser::factory()->create(['telegram_id' => 2001, 'is_active' => true]);
	TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 2002, 'is_active' => true]);

	Http::fake([
		'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$account = Account::factory()->create(['platform' => ['Xbox']]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-CODE',
		'customer_email' => 'client@example.com',
		'platform' => 'Xbox',
		'game' => 'GTA',
		'status' => DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE,
		'account_id' => $account->id,
		'display_login' => 'xbox-login',
		'display_password' => 'QR-1234',
		'display_password_type' => 'fake',
	]);

	$this->postJson(route('delivery.order.connection-code.store', ['token' => $order->token]), [
		'connection_code' => 'AB12CD',
	])
		->assertOk()
		->assertJsonPath('ok', true);

	Http::assertSent(function ($request) use ($order): bool {
		return $request['chat_id'] === '2001'
			&& str_contains($request['text'], 'Connection code submitted')
			&& str_contains($request['text'], 'AB12CD')
			&& $request['reply_markup']['inline_keyboard'][0][0]['callback_data'] === 'delivery:connecting:' . $order->id
			&& $request['reply_markup']['inline_keyboard'][0][1]['callback_data'] === 'delivery:connected:' . $order->id
			&& $request['reply_markup']['inline_keyboard'][1][1]['callback_data'] === 'delivery:extra:' . $order->id . ':1'
			&& isset($request['reply_markup']['inline_keyboard'][2][0]['web_app']['url']);
	});
	Http::assertSent(function ($request) use ($order): bool {
		return $request['chat_id'] === '2002'
			&& str_contains($request['text'], 'Connection code submitted')
			&& isset($request['reply_markup']['inline_keyboard'][2][0]['web_app']['url'])
			&& str_contains($request['reply_markup']['inline_keyboard'][2][0]['web_app']['url'], 'delivery_order=' . $order->id);
	});
});

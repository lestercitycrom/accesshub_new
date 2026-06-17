<?php

declare(strict_types=1);

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Models\DeliveryOrder;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Facades\Http;

function deliveryCallbackPayload(string $callbackId, int $telegramId, string $data): array
{
	return [
		'update_id' => random_int(1000, 9999),
		'callback_query' => [
			'id' => $callbackId,
			'from' => [
				'id' => $telegramId,
				'username' => 'operator_' . $telegramId,
				'first_name' => 'Operator',
			],
			'message' => [
				'message_id' => 10,
				'chat' => [
					'id' => $telegramId,
					'type' => 'private',
				],
			],
			'data' => $data,
		],
	];
}

it('marks delivery order as operator connecting from telegram callback', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	TelegramUser::factory()->create(['telegram_id' => 3001, 'is_active' => true]);
	$order = DeliveryOrder::factory()->create([
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::CONNECTION_CODE_SUBMITTED,
		'account_id' => Account::factory()->create(['platform' => ['Xbox']])->id,
		'last_connection_code' => 'AB12CD',
	]);

	Http::fake([
		'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
	]);

	$this->postJson('/api/telegram/webhook', deliveryCallbackPayload(
		callbackId: 'cb-connecting',
		telegramId: 3001,
		data: 'delivery:connecting:' . $order->id,
	))->assertOk()->assertJson(['status' => 'ok']);

	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::OPERATOR_CONNECTING)
		->and($order->operator_telegram_id)->toBe(3001);

	$this->assertDatabaseHas('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'operator_connecting',
		'actor_id' => '3001',
	]);

	Http::assertSent(function ($request): bool {
		return str_contains($request->url(), 'answerCallbackQuery')
			&& $request['callback_query_id'] === 'cb-connecting'
			&& $request['text'] === 'Order updated.';
	});
});

it('marks delivery order as connected from telegram callback', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	TelegramUser::factory()->create(['telegram_id' => 3002, 'is_active' => true]);
	$order = DeliveryOrder::factory()->create([
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::OPERATOR_CONNECTING,
		'account_id' => Account::factory()->create(['platform' => ['Xbox']])->id,
	]);

	Http::fake([
		'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
	]);

	$this->postJson('/api/telegram/webhook', deliveryCallbackPayload(
		callbackId: 'cb-connected',
		telegramId: 3002,
		data: 'delivery:connected:' . $order->id,
	))->assertOk();

	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::CONNECTED)
		->and($order->operator_telegram_id)->toBe(3002)
		->and($order->connected_at)->not->toBeNull();
});

it('marks delivery order as failed from telegram callback', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	TelegramUser::factory()->create(['telegram_id' => 3003, 'is_active' => true]);
	$order = DeliveryOrder::factory()->create([
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::OPERATOR_CONNECTING,
		'account_id' => Account::factory()->create(['platform' => ['Xbox']])->id,
	]);

	Http::fake([
		'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
	]);

	$this->postJson('/api/telegram/webhook', deliveryCallbackPayload(
		callbackId: 'cb-failed',
		telegramId: 3003,
		data: 'delivery:failed:' . $order->id,
	))->assertOk();

	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::CONNECTION_FAILED)
		->and($order->operator_telegram_id)->toBe(3003);

	$this->assertDatabaseHas('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'connection_failed',
		'actor_id' => '3003',
	]);
});

it('grants extra delivery attempts from telegram callback', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	TelegramUser::factory()->create(['telegram_id' => 3004, 'is_active' => true]);
	$order = DeliveryOrder::factory()->create([
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::LOCKED_24H,
		'account_id' => Account::factory()->create(['platform' => ['Xbox']])->id,
		'connection_attempts_limit' => 3,
		'connection_locked_until' => now()->addDay(),
	]);

	Http::fake([
		'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
	]);

	$this->postJson('/api/telegram/webhook', deliveryCallbackPayload(
		callbackId: 'cb-extra',
		telegramId: 3004,
		data: 'delivery:extra:' . $order->id . ':2',
	))->assertOk();

	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE)
		->and($order->connection_attempts_limit)->toBe(5)
		->and($order->connection_locked_until)->toBeNull()
		->and($order->operator_telegram_id)->toBe(3004);
});

it('refuses to revert a connected order from telegram callback', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	TelegramUser::factory()->create(['telegram_id' => 3006, 'is_active' => true]);
	$order = DeliveryOrder::factory()->create([
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::CONNECTED,
		'account_id' => Account::factory()->create(['platform' => ['Xbox']])->id,
		'connected_at' => now(),
	]);

	Http::fake([
		'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
	]);

	$this->postJson('/api/telegram/webhook', deliveryCallbackPayload(
		callbackId: 'cb-locked-failed',
		telegramId: 3006,
		data: 'delivery:failed:' . $order->id,
	))->assertOk();

	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::CONNECTED);

	$this->assertDatabaseMissing('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'connection_failed',
	]);

	Http::assertSent(function ($request): bool {
		return str_contains($request->url(), 'answerCallbackQuery')
			&& $request['callback_query_id'] === 'cb-locked-failed'
			&& str_contains((string) $request['text'], 'завершен')
			&& $request['show_alert'] === true;
	});
});

it('answers callback with alert when delivery order is missing', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	TelegramUser::factory()->create(['telegram_id' => 3005, 'is_active' => true]);

	Http::fake([
		'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
	]);

	$this->postJson('/api/telegram/webhook', deliveryCallbackPayload(
		callbackId: 'cb-missing',
		telegramId: 3005,
		data: 'delivery:connected:999999',
	))->assertOk();

	Http::assertSent(function ($request): bool {
		return str_contains($request->url(), 'answerCallbackQuery')
			&& $request['callback_query_id'] === 'cb-missing'
			&& $request['text'] === 'Order not found.'
			&& $request['show_alert'] === true;
	});
});

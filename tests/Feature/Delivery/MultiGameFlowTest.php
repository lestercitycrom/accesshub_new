<?php

declare(strict_types=1);

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Facades\Http;

it('runs a per-item connection flow: client code → operator connects that item', function (): void {
	config(['services.telegram.bot_token' => 'test']);
	Http::fake([
		'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
		'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
	]);

	$operator = TelegramUser::factory()->create(['telegram_id' => 7050, 'is_active' => true]);
	Account::factory()->create(['game' => 'Halo', 'platform' => ['Xbox'], 'status' => AccountStatus::ACTIVE, 'available_uses' => 1]);

	$order = DeliveryOrder::factory()->create(['token' => str_repeat('m', 64), 'platform' => 'Steam', 'order_number' => 'ORD-ITEM-FLOW']);
	app(DeliveryOrderService::class)->addGame($order, $operator->telegram_id, 'Halo', 'Xbox');
	$item = $order->items()->first();

	// public status payload lists both the first game (id 0) and the item
	$this->getJson(route('delivery.order.status', ['token' => $order->token]))
		->assertOk()
		->assertJsonPath('items.1.game', 'Halo')
		->assertJsonPath('items.1.id', $item->id);

	// client submits the code for that specific item
	$this->postJson(route('delivery.order.connection-code.store', ['token' => $order->token]), [
		'connection_code' => 'AB12CD',
		'item' => $item->id,
	])->assertOk()->assertJsonPath('ok', true);

	$item->refresh();
	expect($item->status)->toBe(DeliveryOrderStatus::CONNECTION_CODE_SUBMITTED)
		->and($item->last_connection_code)->toBe('AB12CD');

	// operator connects THAT item via telegram callback (i<itemId>)
	$this->postJson('/api/telegram/webhook', [
		'update_id' => 770501,
		'callback_query' => [
			'id' => 'cb-item-connected',
			'from' => ['id' => $operator->telegram_id, 'username' => 'op'],
			'message' => ['message_id' => 5, 'chat' => ['id' => $operator->telegram_id, 'type' => 'private']],
			'data' => 'delivery:connected:i' . $item->id,
		],
	])->assertOk();

	$item->refresh();
	$order->refresh();
	expect($item->status)->toBe(DeliveryOrderStatus::CONNECTED)
		// the first game (order) is untouched by the item action
		->and($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_OPERATOR);
});

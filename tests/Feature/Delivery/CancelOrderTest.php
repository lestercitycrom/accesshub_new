<?php

declare(strict_types=1);

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Models\DeliveryOrder;
use App\Domain\Telegram\Models\TelegramUser;

// P.3: operator cancels an order (refund/chargeback) — creds hidden, status cancelled.
it('cancels an order from the mini app and hides the credentials', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 9501]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-CANCEL',
		'platform' => 'Steam',
		'status' => DeliveryOrderStatus::ACCOUNT_ASSIGNED,
		'display_login' => 'leak@a.com',
		'display_password' => 'secret',
		'token' => str_repeat('c', 64),
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

	$this->postJson("/webapp/api/delivery-orders/{$order->id}/cancel", [])
		->assertOk()
		->assertJsonPath('ok', true);

	$order->refresh();
	expect($order->status)->toBe(DeliveryOrderStatus::CANCELLED)
		->and($order->cancelled_at)->not->toBeNull();

	$this->getJson(route('delivery.order.status', ['token' => $order->token]))
		->assertOk()
		->assertJsonPath('status', 'cancelled')
		->assertJsonPath('account', null);
});

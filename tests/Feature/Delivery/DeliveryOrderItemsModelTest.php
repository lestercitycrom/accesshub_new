<?php

declare(strict_types=1);

use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Models\DeliveryOrderItem;

// P.4 Stage 1: the items model + relation exist and order by position.
it('relates a delivery order to its items ordered by position', function (): void {
	$order = DeliveryOrder::factory()->create();

	DeliveryOrderItem::factory()->create(['delivery_order_id' => $order->id, 'position' => 1, 'game' => 'Second']);
	DeliveryOrderItem::factory()->create(['delivery_order_id' => $order->id, 'position' => 0, 'game' => 'First']);

	$games = $order->refresh()->items->pluck('game')->all();

	expect($games)->toBe(['First', 'Second']);
});

it('casts item status and password type', function (): void {
	$item = DeliveryOrderItem::factory()->create([
		'status' => App\Delivery\Enums\DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE,
		'display_password_type' => App\Delivery\Enums\DeliveryPasswordType::FAKE,
	]);

	expect($item->status)->toBe(App\Delivery\Enums\DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE)
		->and($item->display_password_type)->toBe(App\Delivery\Enums\DeliveryPasswordType::FAKE);
});

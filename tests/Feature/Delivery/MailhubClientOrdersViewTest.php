<?php

declare(strict_types=1);

use App\Delivery\Models\DeliveryOrder;
use Illuminate\Support\Facades\DB;

it('exposes take-order fields through mailhub_client_orders_v1', function (): void {
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-VIEW-1',
		'customer_email' => 'Buyer@Example.com',
		'platform' => 'Xbox',
		'issue_platform' => 'Xbox',
		'game' => 'FC 25',
	]);

	$row = DB::table('mailhub_client_orders_v1')->where('order_id', $order->id)->first();

	expect($row)->not->toBeNull()
		->and($row->order_number)->toBe('ORD-VIEW-1')
		->and($row->customer_email)->toBe('Buyer@Example.com')
		->and($row->platform)->toBe('Xbox')
		->and($row->game)->toBe('FC 25');

	// The read-only contract must expose exactly these columns (locks the shape).
	expect(array_keys((array) $row))->toBe([
		'order_id', 'order_number', 'customer_email', 'platform', 'issue_platform',
		'game', 'status', 'operator_telegram_id', 'issuance_id',
		'created_at', 'updated_at', 'connected_at', 'cancelled_at',
	]);
});

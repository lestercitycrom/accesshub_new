<?php

declare(strict_types=1);

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Enums\DeliveryPasswordType;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;

// P.4 Stage 2: a second game is added as an item; both appear in the payload.
it('adds a second game as an item and lists both games in the payload', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 8901]);
	Account::factory()->create(['game' => 'GTA', 'platform' => ['Steam'], 'login' => 'steam@a.com', 'password' => 'p1', 'status' => AccountStatus::ACTIVE, 'available_uses' => 1]);
	Account::factory()->create(['game' => 'FIFA', 'platform' => ['Epic Games'], 'login' => 'epic@a.com', 'password' => 'p2', 'status' => AccountStatus::ACTIVE, 'available_uses' => 1]);
	$order = DeliveryOrder::factory()->create(['platform' => 'Steam', 'order_number' => 'ORD-MULTI']);

	$svc = app(DeliveryOrderService::class);
	$svc->assignAccount($order, $operator->telegram_id, 'GTA', 'Steam');          // first game → order
	$result = $svc->addGame($order, $operator->telegram_id, 'FIFA', 'Epic Games'); // second → item

	expect($result->successful())->toBeTrue();

	$order->refresh();
	expect($order->items)->toHaveCount(1);

	$payload = $svc->publicPayload($order);
	expect($payload['items'])->toHaveCount(2)
		->and($payload['items'][0]['game'])->toBe('GTA')
		->and($payload['items'][0]['id'])->toBe(0)
		->and($payload['items'][1]['game'])->toBe('FIFA')
		->and($payload['items'][1]['account']['login'])->toBe('epic@a.com')
		->and($payload['items'][1]['id'])->toBeGreaterThan(0);
});

it('adds a console game with a fake password and accepts a code on that item', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 8902]);
	Account::factory()->create(['game' => 'Halo', 'platform' => ['Xbox'], 'login' => 'xbox@a.com', 'password' => 'real-pass', 'status' => AccountStatus::ACTIVE, 'available_uses' => 1]);
	$order = DeliveryOrder::factory()->create(['platform' => 'Steam', 'order_number' => 'ORD-MULTI-QR']);

	$svc = app(DeliveryOrderService::class);
	$result = $svc->addGame($order, $operator->telegram_id, 'Halo', 'Xbox');
	expect($result->successful())->toBeTrue();

	$item = $order->items()->first();
	expect($item->status)->toBe(DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE)
		->and($item->display_password_type)->toBe(DeliveryPasswordType::FAKE)
		->and($item->display_password)->not->toBe('real-pass');

	$sub = $svc->submitConnectionCode($item, 'AB12CD');
	expect($sub->successful())->toBeTrue();

	$item->refresh();
	expect($item->status)->toBe(DeliveryOrderStatus::CONNECTION_CODE_SUBMITTED)
		->and($item->last_connection_code)->toBe('AB12CD');
});

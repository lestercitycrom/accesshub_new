<?php

declare(strict_types=1);

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Enums\DeliveryPasswordType;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;

it('creates a delivery order with token and initial event', function (): void {
	$service = app(DeliveryOrderService::class);

	$order = $service->createFromCustomerInput('ORD-1001', 'Client@Example.com', 'xbox');

	expect($order->token)->toHaveLength(64)
		->and($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_OPERATOR)
		->and($order->customer_email)->toBe('client@example.com')
		->and($order->platform)->toBe('Xbox')
		->and($order->token_expires_at)->not->toBeNull();

	$this->assertDatabaseHas('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'order_created',
	]);
});

it('assigns a fake customer password for connection platforms', function (): void {
	$operator = TelegramUser::factory()->create(['telegram_id' => 8101]);
	Account::factory()->create([
		'game' => 'GTA',
		'platform' => ['Xbox'],
		'login' => 'xbox-login@example.com',
		'password' => 'RealSecret123',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);

	$service = app(DeliveryOrderService::class);
	$order = $service->createFromCustomerInput('ORD-XBOX', 'client@example.com', 'Xbox');

	$result = $service->assignAccount($order, $operator->telegram_id, 'GTA');
	$order->refresh();

	expect($result->successful())->toBeTrue()
		->and($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE)
		->and($order->display_login)->toBe('xbox-login@example.com')
		->and($order->display_password)->not->toBe('RealSecret123')
		->and($order->display_password_type)->toBe(DeliveryPasswordType::FAKE)
		->and($order->account_id)->not->toBeNull()
		->and($order->issuance_id)->not->toBeNull();

	$payload = $service->publicPayload($order);
	expect($payload['account']['password'])->toBe($order->display_password)
		->and($payload['account']['password'])->not->toBe('RealSecret123')
		->and($payload['connection']['required'])->toBeTrue();
});

it('assigns the real password for direct delivery platforms', function (): void {
	$operator = TelegramUser::factory()->create(['telegram_id' => 8102]);
	Account::factory()->create([
		'game' => 'GTA',
		'platform' => ['Steam'],
		'login' => 'steam-login',
		'password' => 'SteamRealPassword',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);

	$service = app(DeliveryOrderService::class);
	$order = $service->createFromCustomerInput('ORD-STEAM', 'client@example.com', 'Steam');

	$result = $service->assignAccount($order, $operator->telegram_id, 'GTA');
	$order->refresh();

	expect($result->successful())->toBeTrue()
		->and($order->status)->toBe(DeliveryOrderStatus::ACCOUNT_ASSIGNED)
		->and($order->display_password)->toBe('SteamRealPassword')
		->and($order->display_password_type)->toBe(DeliveryPasswordType::REAL);

	$payload = $service->publicPayload($order);
	expect($payload['connection']['required'])->toBeFalse();
});

it('limits connection codes and unlocks after the configured timeout', function (): void {
	$service = app(DeliveryOrderService::class);
	$order = DeliveryOrder::factory()->create([
		'platform' => 'Xbox',
		'account_id' => Account::factory()->create(['platform' => ['Xbox']])->id,
		'status' => DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE,
		'connection_attempts_limit' => 3,
	]);

	expect($service->submitConnectionCode($order, 'AB1234')->successful())->toBeTrue();
	expect($service->submitConnectionCode($order, 'CD1234')->successful())->toBeTrue();
	expect($service->submitConnectionCode($order, 'EF1234')->successful())->toBeTrue();

	$locked = $service->submitConnectionCode($order, 'GH1234');
	$order->refresh();

	expect($locked->failed())->toBeTrue()
		->and($order->status)->toBe(DeliveryOrderStatus::LOCKED_24H)
		->and($order->connection_locked_until)->not->toBeNull();

	$order->forceFill([
		'connection_locked_until' => now()->subMinute(),
	])->save();

	$unlocked = $service->submitConnectionCode($order, 'IJ1234');
	$order->refresh();

	expect($unlocked->successful())->toBeTrue()
		->and($order->connection_attempts_used)->toBe(1)
		->and($order->connection_locked_until)->toBeNull();
});

it('rejects connection code submission for direct delivery platforms', function (): void {
	$service = app(DeliveryOrderService::class);
	$order = DeliveryOrder::factory()->create([
		'platform' => 'Steam',
		'status' => DeliveryOrderStatus::ACCOUNT_ASSIGNED,
		'account_id' => Account::factory()->create(['platform' => ['Steam']])->id,
	]);

	$result = $service->submitConnectionCode($order, 'AB1234');

	expect($result->failed())->toBeTrue()
		->and($result->message())->toBe('Connection code is not required for this platform.');
});

it('records operator connection lifecycle actions', function (): void {
	$service = app(DeliveryOrderService::class);
	$operator = TelegramUser::factory()->create(['telegram_id' => 8201]);
	$order = DeliveryOrder::factory()->create([
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::CONNECTION_CODE_SUBMITTED,
		'last_connection_code' => 'AB1234',
		'account_id' => Account::factory()->create(['platform' => ['Xbox']])->id,
	]);

	$service->markOperatorConnecting($order, $operator->telegram_id);
	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::OPERATOR_CONNECTING)
		->and($order->operator_telegram_id)->toBe(8201);

	$service->markConnectionFailed($order, $operator->telegram_id, 'Expired code');
	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::CONNECTION_FAILED);

	$service->grantExtraAttempts($order, $operator->telegram_id, 2);
	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE)
		->and($order->connection_attempts_limit)->toBe(5);

	$service->markConnected($order, $operator->telegram_id);
	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::CONNECTED)
		->and($order->connected_at)->not->toBeNull();

	$this->assertDatabaseHas('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'connected',
		'actor_id' => '8201',
	]);
});

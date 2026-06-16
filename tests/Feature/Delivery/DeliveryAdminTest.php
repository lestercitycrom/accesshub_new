<?php

declare(strict_types=1);

use App\Admin\Livewire\DeliveryOrders\DeliveryOrdersIndex;
use App\Admin\Livewire\DeliveryOrders\DeliveryOrderShow;
use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Enums\DeliveryPasswordType;
use App\Delivery\Models\DeliveryOrder;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Livewire\Livewire;

it('renders delivery orders index for admin users', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	DeliveryOrder::factory()->create([
		'order_number' => 'ORD-DELIVERY-INDEX',
		'customer_email' => 'client@example.com',
		'platform' => 'Xbox',
	]);

	Livewire::test(DeliveryOrdersIndex::class)
		->assertOk()
		->assertSee('Delivery Orders')
		->assertSee('ORD-DELIVERY-INDEX')
		->assertSee('Xbox');
});

it('assigns an account from the admin delivery detail page', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$operator = TelegramUser::factory()->create(['telegram_id' => 9101]);
	$account = Account::factory()->create([
		'game' => 'GTA',
		'platform' => ['Xbox'],
		'login' => 'xbox_login_1',
		'password' => 'real-secret',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 2,
	]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-DELIVERY-ASSIGN',
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
	]);

	Livewire::test(DeliveryOrderShow::class, ['deliveryOrder' => $order])
		->set('operatorTelegramId', (string) $operator->telegram_id)
		->set('game', 'GTA')
		->set('issuePlatform', 'Xbox')
		->call('assignAccount')
		->assertHasNoErrors();

	$order->refresh();
	$account->refresh();

	expect($order->account_id)->toBe($account->id)
		->and($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE)
		->and($order->display_login)->toBe('xbox_login_1')
		->and($order->display_password)->not->toBe('real-secret')
		->and($order->display_password_type)->toBe(DeliveryPasswordType::FAKE)
		->and($account->available_uses)->toBe(1);
});

it('updates connection lifecycle from the admin delivery detail page', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$operator = TelegramUser::factory()->create(['telegram_id' => 9102]);
	$account = Account::factory()->create(['platform' => ['Xbox']]);
	$order = DeliveryOrder::factory()->create([
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::CONNECTION_CODE_SUBMITTED,
		'account_id' => $account->id,
		'connection_attempts_limit' => 3,
		'connection_attempts_used' => 2,
		'last_connection_code' => 'AB1234',
	]);

	Livewire::test(DeliveryOrderShow::class, ['deliveryOrder' => $order])
		->set('operatorTelegramId', (string) $operator->telegram_id)
		->call('markOperatorConnecting')
		->call('grantExtraAttempts')
		->call('markConnected')
		->assertHasNoErrors();

	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::CONNECTED)
		->and($order->operator_telegram_id)->toBe($operator->telegram_id)
		->and($order->connection_attempts_limit)->toBe(4)
		->and($order->connected_at)->not->toBeNull();

	$this->assertDatabaseHas('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'operator_connecting',
	]);

	$this->assertDatabaseHas('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'connected',
	]);
});

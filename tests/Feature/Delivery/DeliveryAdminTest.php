<?php

declare(strict_types=1);

use App\Admin\Livewire\DeliveryOrders\DeliveryOrdersIndex;
use App\Admin\Livewire\DeliveryOrders\DeliveryOrderShow;
use App\Admin\Livewire\DeliveryInstructions\DeliveryInstructionsIndex;
use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Enums\DeliveryPasswordType;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Models\DeliveryPlatformInstruction;
use App\Delivery\Services\DeliveryOrderService;
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

it('keeps admin account assignment retryable after a wrong game', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$operator = TelegramUser::factory()->create(['telegram_id' => 9103]);
	$account = Account::factory()->create([
		'game' => 'NBA 2K26',
		'platform' => ['PS5'],
		'login' => 'ps5_delivery_login',
		'password' => 'real-secret',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-DELIVERY-RETRY',
		'platform' => 'PlayStation',
		'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
	]);

	$component = Livewire::test(DeliveryOrderShow::class, ['deliveryOrder' => $order])
		->assertSee('NBA 2K26')
		->assertSet('issuePlatform', 'PS5')
		->set('operatorTelegramId', (string) $operator->telegram_id)
		->set('game', 'Horizon')
		->call('assignAccount');

	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_OPERATOR)
		->and($order->account_id)->toBeNull()
		->and($account->refresh()->available_uses)->toBe(1);

	$component
		->set('game', 'NBA 2K26')
		->call('assignAccount')
		->assertHasNoErrors();

	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE)
		->and($order->account_id)->toBe($account->id)
		->and($order->issue_platform)->toBe('PS5');
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

it('seeds default delivery platform instructions', function (): void {
	expect(DeliveryPlatformInstruction::query()->where('platform', 'Xbox')->exists())->toBeTrue()
		->and(DeliveryPlatformInstruction::query()->where('platform', 'PlayStation')->exists())->toBeTrue()
		->and(DeliveryPlatformInstruction::query()->where('platform', 'Nintendo')->exists())->toBeTrue();
});

it('updates delivery platform instructions from admin page', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	Livewire::test(DeliveryInstructionsIndex::class)
		->assertOk()
		->assertSee('Delivery Instructions')
		->call('selectPlatform', 'Xbox')
		->set('title', 'Xbox QR code steps')
		->set('body', 'Open the Xbox QR screen and enter the code on this page.')
		->set('isActive', true)
		->call('save')
		->assertHasNoErrors();

	$this->assertDatabaseHas('delivery_platform_instructions', [
		'platform' => 'Xbox',
		'title' => 'Xbox QR code steps',
		'body' => 'Open the Xbox QR screen and enter the code on this page.',
		'is_active' => true,
	]);
});

it('exposes only active delivery platform instructions in public payload', function (): void {
	$service = app(DeliveryOrderService::class);

	DeliveryPlatformInstruction::query()->updateOrCreate(
		['platform' => 'Xbox'],
		[
			'title' => 'Visible Xbox instruction',
			'body' => 'Visible body.',
			'is_active' => true,
		],
	);

	$order = DeliveryOrder::factory()->create(['platform' => 'Xbox']);
	$payload = $service->publicPayload($order);

	expect($payload['instruction']['title'])->toBe('Visible Xbox instruction')
		->and($payload['instruction']['body'])->toBe('Visible body.');

	DeliveryPlatformInstruction::query()
		->where('platform', 'Xbox')
		->update(['is_active' => false]);

	$payload = $service->publicPayload($order);

	expect($payload['instruction'])->toBeNull();
});

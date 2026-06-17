<?php

declare(strict_types=1);

use App\Admin\Livewire\DeliveryOrders\DeliveryOrderShow;
use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Enums\DeliveryPasswordType;
use App\Delivery\Models\DeliveryOrder;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('completes qr platform delivery workflow from public order to telegram callback', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	$admin = User::factory()->create(['is_admin' => true]);
	$operator = TelegramUser::factory()->create(['telegram_id' => 4101, 'is_active' => true]);
	$account = Account::factory()->create([
		'game' => 'GTA',
		'platform' => ['Xbox'],
		'login' => 'xbox_workflow_login',
		'password' => 'real-secret',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);

	Http::fake([
		'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
		'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
	]);

	$this->post(route('delivery.take-order.store'), [
		'order_number' => 'ORD-WORKFLOW-XBOX',
		'email' => 'client@example.com',
		'platform' => 'Xbox',
	])->assertRedirect();

	$order = DeliveryOrder::query()->where('order_number', 'ORD-WORKFLOW-XBOX')->firstOrFail();
	expect($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_OPERATOR);

	$this->actingAs($admin);
	Livewire::test(DeliveryOrderShow::class, ['deliveryOrder' => $order])
		->set('operatorTelegramId', (string) $operator->telegram_id)
		->set('game', 'GTA')
		->set('issuePlatform', 'Xbox')
		->call('assignAccount')
		->assertHasNoErrors();

	$order->refresh();
	$account->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE)
		->and($order->account_id)->toBe($account->id)
		->and($order->display_login)->toBe('xbox_workflow_login')
		->and($order->display_password)->not->toBe('real-secret')
		->and($order->display_password_type)->toBe(DeliveryPasswordType::FAKE)
		->and($account->available_uses)->toBe(0);

	$this->postJson(route('delivery.order.connection-code.store', ['token' => $order->token]), [
		'connection_code' => 'AB12CD',
	])
		->assertOk()
		->assertJsonPath('order.status', DeliveryOrderStatus::CONNECTION_CODE_SUBMITTED->value)
		->assertJsonPath('order.connection.attempts_used', 1);

	$this->postJson('/api/telegram/webhook', [
		'update_id' => 410101,
		'callback_query' => [
			'id' => 'workflow-connected',
			'from' => [
				'id' => $operator->telegram_id,
				'username' => 'workflow_operator',
			],
			'message' => [
				'message_id' => 10,
				'chat' => [
					'id' => $operator->telegram_id,
					'type' => 'private',
				],
			],
			'data' => 'delivery:connected:' . $order->id,
		],
	])->assertOk()->assertJson(['status' => 'ok']);

	$order->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::CONNECTED)
		->and($order->connected_at)->not->toBeNull()
		->and($order->operator_telegram_id)->toBe($operator->telegram_id);

	$this->assertDatabaseHas('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'connection_code_submitted',
	]);
	$this->assertDatabaseHas('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'connected',
	]);
});

it('completes direct platform delivery without connection code', function (): void {
	config(['services.telegram.bot_token' => '']);

	$admin = User::factory()->create(['is_admin' => true]);
	$operator = TelegramUser::factory()->create(['telegram_id' => 4201, 'is_active' => true]);
	Account::factory()->create([
		'game' => 'GTA',
		'platform' => ['Steam'],
		'login' => 'steam_workflow_login',
		'password' => 'steam-real-password',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);

	$this->post(route('delivery.take-order.store'), [
		'order_number' => 'ORD-WORKFLOW-STEAM',
		'email' => 'client@example.com',
		'platform' => 'Steam',
	])->assertRedirect();

	$order = DeliveryOrder::query()->where('order_number', 'ORD-WORKFLOW-STEAM')->firstOrFail();

	$this->actingAs($admin);
	Livewire::test(DeliveryOrderShow::class, ['deliveryOrder' => $order])
		->set('operatorTelegramId', (string) $operator->telegram_id)
		->set('game', 'GTA')
		->set('issuePlatform', 'Steam')
		->call('assignAccount')
		->assertHasNoErrors();

	$this->getJson(route('delivery.order.status', ['token' => $order->token]))
		->assertOk()
		->assertJsonPath('status', DeliveryOrderStatus::ACCOUNT_ASSIGNED->value)
		->assertJsonPath('account.login', 'steam_workflow_login')
		->assertJsonPath('account.password', 'steam-real-password')
		->assertJsonPath('account.password_type', DeliveryPasswordType::REAL->value)
		->assertJsonPath('connection.required', false);

	$this->postJson(route('delivery.order.connection-code.store', ['token' => $order->token]), [
		'connection_code' => 'AB12CD',
	])
		->assertUnprocessable()
		->assertJsonPath('message', 'Connection code is not required for this platform.');
});

<?php

declare(strict_types=1);

use App\Admin\Livewire\DeliveryOrders\DeliveryOrderShow;
use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Models\DeliveryOrder;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Livewire\Livewire;

it('adds a game and runs a per-item action from the mini app', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 9401]);
	Account::factory()->create(['game' => 'GTA', 'platform' => ['Xbox'], 'status' => AccountStatus::ACTIVE, 'available_uses' => 1]);
	$order = DeliveryOrder::factory()->create(['order_number' => 'ORD-S4-MINI', 'platform' => 'Steam']);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

	$this->postJson("/webapp/api/delivery-orders/{$order->id}/add-game", ['game' => 'GTA', 'issue_platform' => 'Xbox'])
		->assertOk()
		->assertJsonPath('ok', true)
		->assertJsonCount(1, 'order.items');

	$item = $order->items()->first();
	expect($item->status)->toBe(DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE);

	$this->postJson("/webapp/api/delivery-orders/{$order->id}/connecting", ['item_id' => $item->id])
		->assertOk()
		->assertJsonPath('ok', true);

	$item->refresh();
	expect($item->status)->toBe(DeliveryOrderStatus::OPERATOR_CONNECTING);
});

it('adds a game and connects that item from the admin panel', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$operator = TelegramUser::factory()->create(['telegram_id' => 9402, 'is_active' => true]);
	Account::factory()->create(['game' => 'GTA', 'platform' => ['Xbox'], 'status' => AccountStatus::ACTIVE, 'available_uses' => 1]);
	$order = DeliveryOrder::factory()->create(['platform' => 'Steam', 'order_number' => 'ORD-S4-ADMIN']);

	$component = Livewire::test(DeliveryOrderShow::class, ['deliveryOrder' => $order])
		->set('operatorTelegramId', (string) $operator->telegram_id)
		->set('newGame', 'GTA')
		->set('newGamePlatform', 'Xbox')
		->call('addGame')
		->assertHasNoErrors();

	$item = $order->items()->first();
	expect($item)->not->toBeNull()
		->and($item->game)->toBe('GTA');

	$component->call('itemConnected', $item->id)->assertHasNoErrors();

	$item->refresh();
	expect($item->status)->toBe(DeliveryOrderStatus::CONNECTED);
});

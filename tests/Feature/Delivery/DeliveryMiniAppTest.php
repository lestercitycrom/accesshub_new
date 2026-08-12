<?php

declare(strict_types=1);

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Enums\DeliveryPasswordType;
use App\Delivery\Models\DeliveryOrder;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Models\TelegramUser;

it('allows delivery operators to work only through delivery mini app endpoints', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 9301]);
	Account::factory()->create([
		'game' => 'GTA',
		'platform' => ['Xbox'],
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-MINI-LIST',
		'platform' => 'Xbox',
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

	$this->getJson('/webapp/api/delivery-orders?delivery_order=' . $order->id)
		->assertOk()
		->assertJsonPath('ok', true)
		->assertJsonPath('items.0.order_number', 'ORD-MINI-LIST')
		->assertJsonPath('items.0.available_games.0.name', 'GTA');

	$this->getJson('/webapp/api/history')
		->assertForbidden()
		->assertJsonPath('error', 'Доступ разрешен только в разделе доставки.');

	$this->postJson('/webapp/api/issue', [
		'order_id' => 'ORD-LEGACY',
		'platform' => 'Xbox',
		'game' => 'GTA',
		'qty' => 1,
	])->assertForbidden();
});

it('assigns a delivery account from telegram mini app with database options', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 9302]);
	$account = Account::factory()->create([
		'game' => 'Horizon',
		'platform' => ['PS5'],
		'login' => 'ps5-mini-login',
		'password' => 'real-password',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-MINI-ASSIGN',
		'platform' => 'PlayStation',
		'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

	$this->getJson("/webapp/api/delivery-orders/{$order->id}/options?issue_platform=PS5")
		->assertOk()
		->assertJsonPath('available_games.0.name', 'Horizon');

	$this->postJson("/webapp/api/delivery-orders/{$order->id}/assign", [
		'game' => 'Horizon',
		'issue_platform' => 'PS5',
	])
		->assertOk()
		->assertJsonPath('ok', true)
		->assertJsonPath('order.account_id', $account->id)
		->assertJsonPath('order.display_login', 'ps5-mini-login')
		->assertJsonPath('order.display_password_type', 'fake');

	$order->refresh();
	$account->refresh();

	expect($order->status)->toBe(DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE)
		->and($order->operator_telegram_id)->toBe($operator->telegram_id)
		->and($order->display_password)->not->toBe('real-password')
		->and($order->display_password_type)->toBe(DeliveryPasswordType::FAKE)
		->and($account->available_uses)->toBe(0);
});

it('attaches an earlier regular issuance to an empty delivery order without spending another use', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 9310]);
	$account = Account::factory()->create([
		'game' => 'Assassins Creed Black Flag Resynced',
		'platform' => ['Epic Games'],
		'login' => 'existing-issuance-login',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 2,
		'max_uses' => 3,
	]);
	$issuance = Issuance::factory()->create([
		'order_id' => '3623233',
		'telegram_id' => $operator->telegram_id,
		'account_id' => $account->id,
		'game' => 'Assassins Creed Black Flag Resynced',
		'platform' => 'Epic Games',
		'payload' => ['qty' => 1],
	]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => '3623233',
		'platform' => 'Epic Games',
		'game' => 'Assassins Creed Black Flag Resynced',
		'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
		'account_id' => null,
		'issuance_id' => null,
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id])
		->postJson("/webapp/api/delivery-orders/{$order->id}/assign", [
			'game' => 'Assassins Creed Black Flag Resynced',
			'issue_platform' => 'Epic Games',
		])
		->assertOk()
		->assertJsonPath('ok', true)
		->assertJsonPath('order.account_id', $account->id)
		->assertJsonPath('order.issuance_id', $issuance->id)
		->assertJsonPath('order.display_login', 'existing-issuance-login');

	expect($account->fresh()->available_uses)->toBe(2)
		->and($order->fresh()->issue_platform)->toBe('Epic Games');
	$this->assertDatabaseHas('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'existing_issuance_attached',
	]);
});

it('does not return duplicate platform aliases in issue platform options', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 9305]);
	Account::factory()->create([
		'game' => 'Fortnite',
		'platform' => ['Epic Games'],
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-MINI-EPIC',
		'platform' => 'Epic Games',
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

	$response = $this->getJson("/webapp/api/delivery-orders/{$order->id}/options?issue_platform=Epic+Games")
		->assertOk();

	$options = $response->json('issue_platform_options');

	expect($options)->toBe(array_values(array_unique($options)))
		->and(collect($options)->filter(fn ($p) => in_array($p, ['Epic Games', 'EpicGames', 'Epic'], true))->values()->all())
		->toBe(['Epic Games']);
});

it('rejects mini app actions on a connected delivery order', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 9306]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-MINI-LOCKED',
		'platform' => 'Xbox',
		'status' => DeliveryOrderStatus::CONNECTED,
		'account_id' => Account::factory()->create(['game' => 'GTA', 'platform' => ['Xbox'], 'status' => AccountStatus::ACTIVE])->id,
		'connected_at' => now(),
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

	$this->postJson("/webapp/api/delivery-orders/{$order->id}/failed", [])
		->assertStatus(422)
		->assertJsonPath('ok', false);

	$this->postJson("/webapp/api/delivery-orders/{$order->id}/extra-attempts", ['amount' => 1])
		->assertStatus(422)
		->assertJsonPath('ok', false);

	$this->postJson("/webapp/api/delivery-orders/{$order->id}/assign", [
		'game' => 'GTA',
		'issue_platform' => 'Xbox',
	])->assertStatus(422)->assertJsonPath('ok', false);

	$order->refresh();
	expect($order->status)->toBe(DeliveryOrderStatus::CONNECTED);
});

it('assigns a specific account chosen by login from the mini app', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 9304]);

	// More available_uses so auto-pick would prefer it over the chosen one.
	$auto = Account::factory()->create([
		'game' => 'FIFA',
		'platform' => ['PS5'],
		'login' => 'ps5-auto-login',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 5,
	]);
	$chosen = Account::factory()->create([
		'game' => 'FIFA',
		'platform' => ['PS5'],
		'login' => 'ps5-chosen-login',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
		'source_label' => Account::SOURCE_ALLKEYSHOP,
	]);

	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-MINI-PICK',
		'platform' => 'PlayStation',
		'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

	$this->getJson("/webapp/api/delivery-orders/{$order->id}/options?issue_platform=PS5&game=FIFA")
		->assertOk()
		->assertJsonPath('ok', true)
		->assertJsonCount(2, 'available_accounts')
		->assertJsonPath('available_accounts.0.platforms', ['PS5'])
		->assertJsonPath('available_accounts.1.source_label', Account::SOURCE_ALLKEYSHOP);

	$this->postJson("/webapp/api/delivery-orders/{$order->id}/assign", [
		'game' => 'FIFA',
		'issue_platform' => 'PS5',
		'account_id' => $chosen->id,
	])
		->assertOk()
		->assertJsonPath('ok', true)
		->assertJsonPath('order.account_id', $chosen->id)
		->assertJsonPath('order.display_login', 'ps5-chosen-login');

	$order->refresh();
	$auto->refresh();

	expect($order->account_id)->toBe($chosen->id)
		->and($auto->available_uses)->toBe(5); // untouched
});

it('replaces a delivery account from mini app and keeps the same client link', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 9303]);
	$first = Account::factory()->create([
		'game' => 'Zelda',
		'platform' => ['Nintendo Switch 2'],
		'login' => 'switch-first',
		'password' => 'first-real',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
		'max_uses' => 1,
	]);
	$second = Account::factory()->create([
		'game' => 'Zelda',
		'platform' => ['Nintendo Switch 2'],
		'login' => 'switch-second',
		'password' => 'second-real',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
		'max_uses' => 1,
	]);
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-MINI-REPLACE',
		'token' => str_repeat('a', 64),
		'platform' => 'Nintendo',
		'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

	$this->postJson("/webapp/api/delivery-orders/{$order->id}/assign", [
		'game' => 'Zelda',
		'issue_platform' => 'Nintendo Switch 2',
	])->assertOk();

	$order->refresh();
	$oldIssuanceId = $order->issuance_id;

	expect($order->account_id)->toBe($first->id);

	$this->postJson("/webapp/api/delivery-orders/{$order->id}/replace", [
		'reason' => 'wrong_password',
	])
		->assertOk()
		->assertJsonPath('ok', true)
		->assertJsonPath('order.account_id', $second->id)
		->assertJsonPath('order.public_url', route('delivery.order.show', ['token' => str_repeat('a', 64)], true))
		->assertJsonPath('order.replacements.0.payload.old_account_id', $first->id)
		->assertJsonPath('order.replacements.0.payload.new_account_id', $second->id);

	$order->refresh();
	$oldIssuance = Issuance::query()->findOrFail($oldIssuanceId);

	expect($order->token)->toBe(str_repeat('a', 64))
		->and($order->account_id)->toBe($second->id)
		->and($order->issuance_id)->not->toBe($oldIssuanceId)
		->and($oldIssuance->payload['replaced'])->toBeTrue()
		->and($oldIssuance->payload['replacement_reason'])->toBe('wrong_password');

	$this->assertDatabaseHas('delivery_events', [
		'delivery_order_id' => $order->id,
		'type' => 'account_replaced',
		'actor_id' => (string) $operator->telegram_id,
	]);
});

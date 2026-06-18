<?php

declare(strict_types=1);

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;

// P.1: re-issuing ("Выдать другой аккаунт") returns the use to the previous account.
it('returns the use to the previous account on re-issue', function (): void {
	$operator = TelegramUser::factory()->deliveryOperator()->create(['telegram_id' => 8801]);
	$first = Account::factory()->create([
		'game' => 'GTA', 'platform' => ['PS5'], 'login' => 'first@a.com',
		'status' => AccountStatus::ACTIVE, 'available_uses' => 1, 'max_uses' => 1,
	]);
	$second = Account::factory()->create([
		'game' => 'GTA', 'platform' => ['PS5'], 'login' => 'second@a.com',
		'status' => AccountStatus::ACTIVE, 'available_uses' => 1, 'max_uses' => 1,
	]);
	$order = DeliveryOrder::factory()->create(['order_number' => 'ORD-REISSUE', 'platform' => 'PlayStation']);

	$orders = app(DeliveryOrderService::class);
	$orders->assignAccount($order, $operator->telegram_id, 'GTA', 'PS5', $first->id);
	$first->refresh();
	expect($first->available_uses)->toBe(0);

	// Re-issue, forcing the second account.
	$orders->assignAccount($order, $operator->telegram_id, 'GTA', 'PS5', $second->id);

	$first->refresh();
	$second->refresh();
	$order->refresh();

	expect($order->account_id)->toBe($second->id)
		->and($second->available_uses)->toBe(0)
		->and($first->available_uses)->toBe(1); // returned to the pool
});

// P.2: an expired link no longer exposes the credentials.
it('hides credentials once the link has expired', function (): void {
	$order = DeliveryOrder::factory()->create([
		'order_number' => 'ORD-EXPIRED',
		'platform' => 'Steam',
		'status' => DeliveryOrderStatus::ACCOUNT_ASSIGNED,
		'display_login' => 'leak@a.com',
		'display_password' => 'secret-pass',
		'token_expires_at' => now()->subHour(),
	]);

	$this->getJson(route('delivery.order.status', ['token' => $order->token]))
		->assertOk()
		->assertJsonPath('status', DeliveryOrderStatus::EXPIRED->value)
		->assertJsonPath('account', null);
});

// P.5: at most 2 orders per email per hour.
it('limits orders to 2 per email per hour', function (): void {
	$payload = fn (string $n) => [
		'order_number' => $n, 'email' => 'spammer@example.com', 'platform' => 'Steam',
	];

	$this->post(route('delivery.take-order.store'), $payload('SPAM-1'))->assertRedirect();
	$this->post(route('delivery.take-order.store'), $payload('SPAM-2'))->assertRedirect();
	$this->from(route('delivery.take-order'))
		->post(route('delivery.take-order.store'), $payload('SPAM-3'))
		->assertRedirect(route('delivery.take-order'))
		->assertSessionHas('error');

	expect(DeliveryOrder::query()->where('customer_email', 'spammer@example.com')->count())->toBe(2);
});

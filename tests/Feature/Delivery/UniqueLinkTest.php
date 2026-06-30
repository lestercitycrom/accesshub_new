<?php

declare(strict_types=1);

use App\Admin\Livewire\DeliveryLinks\DeliveryLinksIndex;
use App\Delivery\Models\DeliveryLink;
use App\Delivery\Models\DeliveryOrder;
use App\Models\User;
use Livewire\Livewire;

it('opens the take-order form for an unused unique link', function (): void {
	$link = DeliveryLink::factory()->create();

	$this->get(route('delivery.take-order.coded', ['code' => $link->code]))
		->assertOk()
		->assertSee('Get your game', false)
		->assertSee(route('delivery.take-order.coded.store', ['code' => $link->code]), false);
});

it('returns 404 for an unknown unique link', function (): void {
	$this->get(route('delivery.take-order.coded', ['code' => 'doesnotexist1234']))
		->assertNotFound();
});

it('redirects a used unique link to its order', function (): void {
	$order = DeliveryOrder::factory()->create(['token' => str_repeat('a', 64)]);
	$link = DeliveryLink::factory()->used()->create(['delivery_order_id' => $order->id]);

	$this->get(route('delivery.take-order.coded', ['code' => $link->code]))
		->assertRedirect(route('delivery.order.show', ['token' => $order->token]));
});

it('creates an order from a unique link and consumes it', function (): void {
	$link = DeliveryLink::factory()->create(['game' => 'Alan Wake 2']);

	$response = $this->post(route('delivery.take-order.coded.store', ['code' => $link->code]), [
		'order_number' => '3233159',
		'email' => 'buyer@example.com',
		'platform' => 'Steam',
	]);

	$order = DeliveryOrder::query()->where('order_number', '3233159')->firstOrFail();
	$response->assertRedirect(route('delivery.order.show', ['token' => $order->token]));

	$link->refresh();
	expect($order->game)->toBe('Alan Wake 2')
		->and($link->used_at)->not->toBeNull()
		->and($link->delivery_order_id)->toBe($order->id);
});

it('does not create a second order when a unique link is reused', function (): void {
	$order = DeliveryOrder::factory()->create(['token' => str_repeat('b', 64)]);
	$link = DeliveryLink::factory()->used()->create(['delivery_order_id' => $order->id]);

	$response = $this->post(route('delivery.take-order.coded.store', ['code' => $link->code]), [
		'order_number' => 'SHOULD-NOT-CREATE',
		'email' => 'buyer@example.com',
		'platform' => 'Steam',
	]);

	$response->assertRedirect(route('delivery.order.show', ['token' => $order->token]));
	expect(DeliveryOrder::query()->where('order_number', 'SHOULD-NOT-CREATE')->exists())->toBeFalse();
});

it('returns 404 when posting to an unknown unique link', function (): void {
	$this->post(route('delivery.take-order.coded.store', ['code' => 'nope000000000000']), [
		'order_number' => '3233159',
		'email' => 'buyer@example.com',
		'platform' => 'Steam',
	])->assertNotFound();
});

it('bulk-generates a batch of unique links from the admin', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	Livewire::test(DeliveryLinksIndex::class)
		->set('count', 25)
		->set('game', 'EA FC 25')
		->set('note', 'EA FC 25 PS5')
		->call('generate')
		->assertHasNoErrors()
		->assertSee('EA FC 25');

	expect(DeliveryLink::query()->count())->toBe(25)
		->and(DeliveryLink::query()->where('game', 'EA FC 25')->count())->toBe(25)
		->and(DeliveryLink::query()->whereNotNull('batch')->where('note', 'EA FC 25 PS5')->count())->toBe(25)
		->and(DeliveryLink::query()->distinct()->count('code'))->toBe(25);
});

it('exports unused links as newline-separated urls', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$unused = DeliveryLink::factory()->create(['batch' => 'b1']);
	$used = DeliveryLink::factory()->used()->create(['batch' => 'b1']);

	$body = $this->get(route('admin.export.delivery-links.csv', ['batch' => 'b1', 'only' => 'unused']))
		->assertOk()
		->getContent();

	expect($body)->toContain(route('delivery.take-order.coded', ['code' => $unused->code]))
		->and($body)->not->toContain($used->code);
});

<?php

declare(strict_types=1);

use App\Delivery\Models\DeliveryOrder;
use Carbon\CarbonImmutable;

afterEach(function (): void {
	CarbonImmutable::setTestNow();
});

it('blocks orders outside support hours when enforced', function (): void {
	config([
		'delivery.working_hours.enforce' => true,
		'delivery.working_hours.start' => 9,
		'delivery.working_hours.end' => 23,
		'delivery.working_hours.timezone' => 'Europe/Kyiv',
	]);
	CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-18 03:00', 'Europe/Kiev'));

	$this->from(route('delivery.take-order'))
		->post(route('delivery.take-order.store'), [
			'order_number' => 'NIGHT-BLOCK-1',
			'email' => 'client@example.com',
			'platform' => 'Xbox',
		])
		->assertRedirect(route('delivery.take-order'))
		->assertSessionHas('error');

	expect(DeliveryOrder::query()->where('order_number', 'NIGHT-BLOCK-1')->exists())->toBeFalse();
});

it('accepts orders during support hours when enforced', function (): void {
	config([
		'delivery.working_hours.enforce' => true,
		'delivery.working_hours.start' => 9,
		'delivery.working_hours.end' => 23,
		'delivery.working_hours.timezone' => 'Europe/Kyiv',
	]);
	CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-18 12:00', 'Europe/Kiev'));

	$this->post(route('delivery.take-order.store'), [
		'order_number' => 'DAY-OK-1',
		'email' => 'client@example.com',
		'platform' => 'Xbox',
	])->assertRedirect();

	expect(DeliveryOrder::query()->where('order_number', 'DAY-OK-1')->exists())->toBeTrue();
});

it('accepts orders after midnight for an overnight window (09:00 -> 03:00)', function (): void {
	config([
		'delivery.working_hours.enforce' => true,
		'delivery.working_hours.start' => 9,
		'delivery.working_hours.end' => 3,
		'delivery.working_hours.timezone' => 'Europe/Kyiv',
	]);
	CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-18 02:00', 'Europe/Kiev'));

	$this->post(route('delivery.take-order.store'), [
		'order_number' => 'NIGHT-OK-1',
		'email' => 'client@example.com',
		'platform' => 'Xbox',
	])->assertRedirect();

	expect(DeliveryOrder::query()->where('order_number', 'NIGHT-OK-1')->exists())->toBeTrue();
});

it('blocks orders after the overnight window closes (09:00 -> 03:00, at 05:00)', function (): void {
	config([
		'delivery.working_hours.enforce' => true,
		'delivery.working_hours.start' => 9,
		'delivery.working_hours.end' => 3,
		'delivery.working_hours.timezone' => 'Europe/Kyiv',
	]);
	CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-18 05:00', 'Europe/Kiev'));

	$this->from(route('delivery.take-order'))
		->post(route('delivery.take-order.store'), [
			'order_number' => 'NIGHT-BLOCK-2',
			'email' => 'client@example.com',
			'platform' => 'Xbox',
		])
		->assertRedirect(route('delivery.take-order'))
		->assertSessionHas('error');

	expect(DeliveryOrder::query()->where('order_number', 'NIGHT-BLOCK-2')->exists())->toBeFalse();
});

it('does not block when working hours are not enforced (default)', function (): void {
	config(['delivery.working_hours.enforce' => false]);
	CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-18 03:00', 'Europe/Kiev'));

	$this->post(route('delivery.take-order.store'), [
		'order_number' => 'NO-ENFORCE-1',
		'email' => 'client@example.com',
		'platform' => 'Steam',
	])->assertRedirect();

	expect(DeliveryOrder::query()->where('order_number', 'NO-ENFORCE-1')->exists())->toBeTrue();
});

it('exposes the installation tutorial url for steam and epic only', function (): void {
	$steam = DeliveryOrder::factory()->create(['platform' => 'Steam', 'token' => str_repeat('s', 64)]);
	$this->getJson(route('delivery.order.status', ['token' => $steam->token]))
		->assertOk()
		->assertJsonPath('tutorial_url', config('delivery.tutorial_urls')['Steam']);

	$xbox = DeliveryOrder::factory()->create(['platform' => 'Xbox', 'token' => str_repeat('x', 64)]);
	$this->getJson(route('delivery.order.status', ['token' => $xbox->token]))
		->assertOk()
		->assertJsonPath('tutorial_url', null);
});

it('fails open (does not 500) when the configured timezone is invalid', function (): void {
	config([
		'delivery.working_hours.enforce' => true,
		'delivery.working_hours.timezone' => 'Definitely/Not_A_Timezone',
	]);

	$this->post(route('delivery.take-order.store'), [
		'order_number' => 'BAD-TZ-1',
		'email' => 'client@example.com',
		'platform' => 'Steam',
	])->assertRedirect();

	// Fail-open: order is accepted rather than crashing the public form.
	expect(DeliveryOrder::query()->where('order_number', 'BAD-TZ-1')->exists())->toBeTrue();
});

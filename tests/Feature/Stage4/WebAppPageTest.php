<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders webapp page', function (): void {
	$response = $this->get('/webapp');

	$response->assertOk();
	$response->assertSee('Выдача');
	$response->assertSee('История');
});

it('shows issue form inputs', function (): void {
	$response = $this->get('/webapp');

	$response->assertOk();
	$response->assertSee('Номер заказа');
	$response->assertSee('Количество');
	$response->assertSee('steam / xbox');
	$response->assertSee('cs2 / minecraft');
});


<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders webapp page', function (): void {
	$response = $this->get('/webapp');

	$response->assertOk();
	$response->assertSee('AccessHub WebApp');
})->group('Stage51');
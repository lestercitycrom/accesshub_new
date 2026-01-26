<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders webapp page without bootstrap status block', function (): void {
	config()->set('accesshub.webapp.verify_init_data', false);

	$response = $this->get('/webapp');

	$response->assertOk();
	$response->assertSee('Выдача');
	$response->assertSee('История');
})->group('Stage51');


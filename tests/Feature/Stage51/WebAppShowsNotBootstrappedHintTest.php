<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows not bootstrapped status when session has no telegram id', function (): void {
	config()->set('accesshub.webapp.verify_init_data', false);

	$response = $this->get('/webapp');

	$response->assertOk();
	$response->assertSee('Не инициализировано');
	$response->assertSee('Тестовый bootstrap');
})->group('Stage51');

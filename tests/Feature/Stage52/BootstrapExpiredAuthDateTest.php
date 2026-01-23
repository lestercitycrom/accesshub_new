<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TelegramInitDataFactory;

uses(RefreshDatabase::class);

it('rejects expired auth_date when verification enabled', function (): void {
	config()->set('accesshub.webapp.verify_init_data', true);
	config()->set('accesshub.webapp.max_auth_age_seconds', 60);
	config()->set('services.telegram.bot_token', 'test');

	$initData = TelegramInitDataFactory::make('test', [
		'id' => 111,
		'first_name' => 'Test',
		'username' => 'test_user',
	], time() - 3600);

	$response = $this->postJson('/webapp/bootstrap', [
		'initData' => $initData,
	]);

	$response->assertStatus(403);
	$response->assertSeeText('auth_date expired');
})->group('Stage52');
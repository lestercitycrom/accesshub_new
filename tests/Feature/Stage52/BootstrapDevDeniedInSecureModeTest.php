<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('denies dev bootstrap when verification enabled', function (): void {
	config()->set('accesshub.webapp.verify_init_data', true);
	config()->set('services.telegram.bot_token', 'test');

	$response = $this->postJson('/webapp/bootstrap', [
		'telegram_id' => 111,
	]);

	$response->assertStatus(403);
	$response->assertSeeText('Empty initData');
})->group('Stage52');
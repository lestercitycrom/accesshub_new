<?php

declare(strict_types=1);

use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TelegramInitDataFactory;

uses(RefreshDatabase::class);

it('rejects invalid initData when verification enabled', function (): void {
	config()->set('accesshub.webapp.verify_init_data', true);
	config()->set('services.telegram.bot_token', 'test');

	$response = $this->postJson('/webapp/bootstrap', [
		'initData' => 'auth_date=1&user=%7B%7D&hash=bad',
	]);

	$response->assertStatus(403);
})->group('Stage4');

it('accepts valid initData when verification enabled', function (): void {
	config()->set('accesshub.webapp.verify_init_data', true);
	config()->set('accesshub.webapp.max_auth_age_seconds', 86400);
	config()->set('services.telegram.bot_token', 'test');

	$initData = TelegramInitDataFactory::make('test', [
		'id' => 111,
		'first_name' => 'Test',
		'username' => 'test_user',
	]);

	$response = $this->postJson('/webapp/bootstrap', [
		'initData' => $initData,
	]);

	$response->assertNoContent();

	expect((int) session()->get('webapp.telegram_id'))->toBe(111);
	expect(TelegramUser::query()->where('telegram_id', 111)->exists())->toBeTrue();
})->group('Stage4');
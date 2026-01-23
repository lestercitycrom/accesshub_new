<?php

declare(strict_types=1);

use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows dev bootstrap when verification disabled', function (): void {
	config()->set('accesshub.webapp.verify_init_data', false);

	$response = $this->postJson('/webapp/bootstrap', [
		'telegram_id' => 111,
		'username' => 'dev_user',
		'first_name' => 'Dev',
		'last_name' => 'User',
	]);

	$response->assertNoContent();

	expect((int) session()->get('webapp.telegram_id'))->toBe(111);
	expect(TelegramUser::query()->where('telegram_id', 111)->exists())->toBeTrue();
})->group('Stage4');
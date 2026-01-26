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
	$user = TelegramUser::query()->where('telegram_id', 111)->first();
	expect($user)->not->toBeNull();
	expect($user->is_active)->toBeFalse();
})->group('Stage4');

it('does not override existing user role or active status on dev bootstrap', function (): void {
	config()->set('accesshub.webapp.verify_init_data', false);

	TelegramUser::factory()->admin()->create([
		'telegram_id' => 222,
		'is_active' => true,
	]);

	$response = $this->postJson('/webapp/bootstrap', [
		'telegram_id' => 222,
		'username' => 'dev_user',
		'first_name' => 'Dev',
		'last_name' => 'User',
	]);

	$response->assertNoContent();

	$user = TelegramUser::query()->where('telegram_id', 222)->first();
	expect($user)->not->toBeNull();
	expect($user->role->value)->toBe('admin');
	expect($user->is_active)->toBeTrue();
})->group('Stage4');

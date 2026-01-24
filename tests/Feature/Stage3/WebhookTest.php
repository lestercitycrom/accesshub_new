<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('handles text message webhook successfully', function (): void {
	config()->set('services.telegram.bot_token', 'test');

	// Setup test data
	$telegramUser = TelegramUser::factory()->create(['telegram_id' => 987654321]);
	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'login' => 'testlogin',
		'password' => 'testpass',
	]);
	Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'login' => 'testlogin2',
		'password' => 'testpass2',
	]);

	// Mock Telegram API
	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);

	// Load fixture
	$fixture = json_decode(file_get_contents(base_path('tests/Fixtures/telegram/text_update.json')), true);

	// Send request
	$response = $this->postJson('/api/telegram/webhook', $fixture);

	$response->assertStatus(200)
		->assertJson(['status' => 'ok']);

	// Verify Telegram API was called
	Http::assertSent(function ($request): bool {
		return str_contains($request->url(), 'sendMessage')
			&& str_contains($request['text'], '✅ Выдано')
			&& str_contains($request['text'], 'testlogin')
			&& str_contains($request['text'], 'testpass');
	});

	// Verify database changes
	expect($account->issuances()->count())->toBe(1);
	expect($account->events()->where('type', 'ISSUED')->count())->toBe(1);
});

it('handles webapp data webhook successfully', function (): void {
	config()->set('services.telegram.bot_token', 'test');

	// Setup test data
	$telegramUser = TelegramUser::factory()->create(['telegram_id' => 987654321]);
	$account = Account::factory()->create([
		'game' => 'dota2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
		'login' => 'webapplogin',
		'password' => 'webapppass',
	]);

	// Mock Telegram API
	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);

	// Load fixture
	$fixture = json_decode(file_get_contents(base_path('tests/Fixtures/telegram/webapp_update.json')), true);

	// Send request
	$response = $this->postJson('/api/telegram/webhook', $fixture);

	$response->assertStatus(200)
		->assertJson(['status' => 'ok']);

	// Verify Telegram API was called with correct data
	Http::assertSent(function ($request): bool {
		return str_contains($request->url(), 'sendMessage')
			&& str_contains($request['text'], 'webapplogin')
			&& str_contains($request['text'], 'webapppass');
	});

	// Verify database changes
	expect($account->issuances()->count())->toBe(1);
	$issuance = $account->issuances()->first();
	expect($issuance->order_id)->toBe('ORD-67890');
	expect($issuance->game)->toBe('dota2');
	expect($issuance->platform)->toBe('steam');
	expect($issuance->qty)->toBe(1);
});

it('auto registers telegram user', function (): void {
	config()->set('services.telegram.bot_token', 'test');

	// Load fixture
	$fixture = json_decode(file_get_contents(base_path('tests/Fixtures/telegram/text_update.json')), true);

	// Send request
	$this->postJson('/api/telegram/webhook', $fixture);

	// Verify user was created
	$user = TelegramUser::where('telegram_id', 123456789)->first();
	expect($user)->not->toBeNull();
	expect($user->username)->toBe('testuser');
	expect($user->first_name)->toBe('Test');
	expect($user->last_name)->toBe('User');
	expect($user->is_active)->toBeTrue();
});

it('handles invalid update gracefully', function (): void {
	$invalidUpdate = ['invalid' => 'data'];

	$response = $this->postJson('/api/telegram/webhook', $invalidUpdate);

	$response->assertStatus(200)
		->assertJson(['status' => 'ignored']);
});

it('returns error message for invalid format', function (): void {
	config()->set('services.telegram.bot_token', 'test');

	TelegramUser::factory()->create(['telegram_id' => 123456789]);

	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$fixture = json_decode(file_get_contents(base_path('tests/Fixtures/telegram/text_update.json')), true);
	$fixture['message']['text'] = 'invalid format';

	$response = $this->postJson('/api/telegram/webhook', $fixture);

	$response->assertStatus(200);

	Http::assertSent(function ($request): bool {
		return str_contains($request['text'], 'Неверный формат запроса');
	});
});

it('returns error message when no accounts available', function (): void {
	config()->set('services.telegram.bot_token', 'test');

	// Create telegram user but no accounts
	TelegramUser::factory()->create(['telegram_id' => 123456789]);

	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$fixture = json_decode(file_get_contents(base_path('tests/Fixtures/telegram/text_update.json')), true);

	$response = $this->postJson('/api/telegram/webhook', $fixture);

	$response->assertStatus(200);

	Http::assertSent(function ($request): bool {
		return str_contains($request['text'], 'Ошибка выдачи: Недостаточно доступных аккаунтов.');
	});
});

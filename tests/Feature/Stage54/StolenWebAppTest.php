<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('allows operator to postpone stolen by one day', function (): void {
	config()->set('services.telegram.bot_token', 'test');
	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$operator = TelegramUser::factory()->create(['telegram_id' => 111]);

	$account = Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'assigned_to_telegram_id' => $operator->telegram_id,
		'status_deadline_at' => now(),
	]);

	$oldDeadline = $account->status_deadline_at;

	$payload = [
		'update_id' => 20001,
		'message' => [
			'message_id' => 1,
			'from' => [
				'id' => $operator->telegram_id,
				'is_bot' => false,
				'first_name' => 'Test',
			],
			'chat' => [
				'id' => $operator->telegram_id,
				'type' => 'private',
			],
			'date' => time(),
			'web_app_data' => [
				'data' => json_encode([
					'action' => 'postpone_stolen',
					'payload' => [
						'account_id' => $account->id,
					],
				]),
			],
		],
	];

	$response = $this->postJson('/api/telegram/webhook', $payload);

	$response->assertStatus(200)
		->assertJson(['status' => 'ok']);

	$account->refresh();
	expect($account->status_deadline_at)->not->toEqual($oldDeadline);

	expect(AccountEvent::query()
		->where('account_id', $account->id)
		->where('type', 'EXTEND_DEADLINE')
		->exists())->toBeTrue();
});

it('allows operator to recover stolen via password form', function (): void {
	config()->set('services.telegram.bot_token', 'test');
	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$operator = TelegramUser::factory()->create(['telegram_id' => 222]);

	$account = Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'assigned_to_telegram_id' => $operator->telegram_id,
		'status_deadline_at' => now(),
		'flags' => ['ACTION_REQUIRED' => true],
	]);

	$payload = [
		'update_id' => 20002,
		'message' => [
			'message_id' => 1,
			'from' => [
				'id' => $operator->telegram_id,
				'is_bot' => false,
				'first_name' => 'Test',
			],
			'chat' => [
				'id' => $operator->telegram_id,
				'type' => 'private',
			],
			'date' => time(),
			'web_app_data' => [
				'data' => json_encode([
					'action' => 'recover_stolen',
					'payload' => [
						'account_id' => $account->id,
						'password' => 'new_stolen_pass',
					],
				]),
			],
		],
	];

	$response = $this->postJson('/api/telegram/webhook', $payload);

	$response->assertStatus(200)
		->assertJson(['status' => 'ok']);

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::ACTIVE);
	expect($account->assigned_to_telegram_id)->toBeNull();
	expect($account->status_deadline_at)->toBeNull();
	expect($account->flags)->not->toHaveKey('ACTION_REQUIRED');

	expect(AccountEvent::query()
		->where('account_id', $account->id)
		->where('type', 'STOLEN_RECOVERED')
		->exists())->toBeTrue();
});


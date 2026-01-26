<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('marks problem wrong_password and creates account event', function (): void {
	config()->set('services.telegram.bot_token', 'test');
	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$telegramUser = TelegramUser::factory()->create(['telegram_id' => 111]);
	$telegramId = $telegramUser->telegram_id;

	$account = Account::factory()->create([
		'status' => AccountStatus::ACTIVE,
	]);

	Issuance::factory()->create([
		'telegram_id' => $telegramId,
		'account_id' => $account->id,
		'game' => 'cs2',
		'platform' => 'steam',
		'qty' => 1,
		'order_id' => 'ORD-1',
	]);

	$payload = [
		'update_id' => 10002,
		'message' => [
			'message_id' => 1,
			'from' => [
				'id' => $telegramId,
				'is_bot' => false,
				'first_name' => 'Test',
			],
			'chat' => [
				'id' => $telegramId,
				'type' => 'private',
			],
			'date' => time(),
			'web_app_data' => [
				'data' => json_encode([
					'action' => 'mark_problem',
					'payload' => [
						'account_id' => $account->id,
						'reason' => 'wrong_password',
					],
				]),
			],
		],
	];

	$response = $this->postJson('/api/telegram/webhook', $payload);

	$response->assertStatus(200)
		->assertJson(['status' => 'ok']);

	expect(AccountEvent::query()
		->where('account_id', $account->id)
		->where('telegram_id', $telegramId)
		->where('type', 'MARK_PROBLEM')
		->exists())->toBeTrue();
})->group('Stage53');


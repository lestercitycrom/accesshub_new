<?php

declare(strict_types=1);

use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 403 for /webapp/api/me when not bootstrapped', function (): void {
	$response = $this->getJson('/webapp/api/me');
	$response->assertStatus(403);
});

it('returns current operator info for /webapp/api/me', function (): void {
	$telegramUser = TelegramUser::factory()->create(['telegram_id' => 555]);

	$this->withSession(['webapp.telegram_id' => $telegramUser->telegram_id]);

	$response = $this->getJson('/webapp/api/me');
	$response->assertOk()
		->assertJson([
			'telegram_id' => 555,
			'role' => 'operator',
			'is_active' => true,
		]);
});

it('returns operator history for /webapp/api/history', function (): void {
	$telegramUser = TelegramUser::factory()->create(['telegram_id' => 777]);
	$account = Account::factory()->create();

	Issuance::factory()->create([
		'telegram_id' => $telegramUser->telegram_id,
		'account_id' => $account->id,
		'order_id' => 'ORD-1',
	]);

	Issuance::factory()->create([
		'telegram_id' => $telegramUser->telegram_id,
		'account_id' => $account->id,
		'order_id' => 'ORD-2',
	]);

	$this->withSession(['webapp.telegram_id' => $telegramUser->telegram_id]);

	$response = $this->getJson('/webapp/api/history?limit=50');
	$response->assertOk();

	$items = $response->json('items');
	expect($items)->toHaveCount(2);
});

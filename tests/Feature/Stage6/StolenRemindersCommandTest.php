<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Telegram\Models\TelegramUser;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('sends reminders only for overdue stolen accounts', function (): void {
	config()->set('services.telegram.bot_token', 'test');

	$telegramUser = TelegramUser::factory()->create(['telegram_id' => 111]);

	$overdue = Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'assigned_to_telegram_id' => $telegramUser->telegram_id,
		'status_deadline_at' => CarbonImmutable::now()->subDay(),
	]);

	Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'assigned_to_telegram_id' => $telegramUser->telegram_id,
		'status_deadline_at' => CarbonImmutable::now()->addDay(),
	]);

	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);

	Artisan::call('accesshub:stolen-remind');

	Http::assertSentCount(1);
	Http::assertSent(function ($request) use ($overdue): bool {
		return str_contains($request->url(), 'sendMessage')
			&& str_contains($request['text'], 'STOLEN')
			&& str_contains($request['text'], (string) $overdue->id);
	});

	expect(AccountEvent::query()
		->where('account_id', $overdue->id)
		->where('type', 'STOLEN_REMINDER_SENT')
		->exists())->toBeTrue();
});

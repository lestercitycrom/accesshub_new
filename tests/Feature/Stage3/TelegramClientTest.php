<?php

declare(strict_types=1);

use App\Telegram\Services\TelegramClient;
use Illuminate\Support\Facades\Http;

it('sends message successfully', function (): void {
	Http::fake([
		'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$client = new TelegramClient();

	$result = $client->sendMessage('123456789', 'Test message');

	expect($result)->toBeTrue();

	Http::assertSent(function ($request): bool {
		return $request->url() === 'https://api.telegram.org/bottest/sendMessage'
			&& $request['chat_id'] === '123456789'
			&& $request['text'] === 'Test message'
			&& $request['parse_mode'] === 'HTML';
	});
});

it('returns false when bot token is empty', function (): void {
	config(['services.telegram.bot_token' => '']);

	$client = new TelegramClient();

	$result = $client->sendMessage('123456789', 'Test message');

	expect($result)->toBeFalse();
});

it('returns false on http error', function (): void {
	Http::fake([
		'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => false], 400),
	]);

	$client = new TelegramClient();

	$result = $client->sendMessage('123456789', 'Test message');

	expect($result)->toBeFalse();
});
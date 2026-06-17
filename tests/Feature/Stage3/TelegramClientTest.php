<?php

declare(strict_types=1);

use App\Telegram\Services\TelegramClient;
use Illuminate\Support\Facades\Http;

it('sends message successfully', function (): void {
	config(['services.telegram.bot_token' => 'test']);

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

it('sends message with reply markup', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	Http::fake([
		'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
	]);

	$client = new TelegramClient();

	$result = $client->sendMessage('123456789', 'Test message', replyMarkup: [
		'inline_keyboard' => [
			[
				['text' => 'Open', 'url' => 'https://example.test'],
			],
		],
	]);

	expect($result)->toBeTrue();

	Http::assertSent(function ($request): bool {
		return $request->url() === 'https://api.telegram.org/bottest/sendMessage'
			&& $request['reply_markup']['inline_keyboard'][0][0]['text'] === 'Open'
			&& $request['reply_markup']['inline_keyboard'][0][0]['url'] === 'https://example.test';
	});
});

it('answers callback query successfully', function (): void {
	config(['services.telegram.bot_token' => 'test']);

	Http::fake([
		'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
	]);

	$client = new TelegramClient();

	$result = $client->answerCallbackQuery('callback-1', 'Updated.');

	expect($result)->toBeTrue();

	Http::assertSent(function ($request): bool {
		return $request->url() === 'https://api.telegram.org/bottest/answerCallbackQuery'
			&& $request['callback_query_id'] === 'callback-1'
			&& $request['text'] === 'Updated.'
			&& $request['show_alert'] === false;
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

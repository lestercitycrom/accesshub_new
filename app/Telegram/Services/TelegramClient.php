<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use Illuminate\Support\Facades\Http;

final class TelegramClient
{
	public function sendMessage(
		string $chatId,
		string $text,
		bool $disableLinkPreview = false,
		?array $replyMarkup = null,
	): bool
	{
		$botToken = config('services.telegram.bot_token');

		if (empty($botToken)) {
			return false;
		}

		$url = "https://api.telegram.org/bot{$botToken}/sendMessage";

		$payload = [
			'chat_id' => $chatId,
			'text' => $text,
			'parse_mode' => 'HTML',
		];

		if ($disableLinkPreview) {
			$payload['link_preview_options'] = ['is_disabled' => true];
		}

		if ($replyMarkup !== null) {
			$payload['reply_markup'] = $replyMarkup;
		}

		$response = Http::post($url, $payload);

		return $response->successful();
	}

	public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): bool
	{
		$botToken = config('services.telegram.bot_token');

		if (empty($botToken)) {
			return false;
		}

		$payload = [
			'callback_query_id' => $callbackQueryId,
			'show_alert' => $showAlert,
		];

		if ($text !== null && $text !== '') {
			$payload['text'] = $text;
		}

		$response = Http::post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", $payload);

		return $response->successful();
	}

	public function setChatMenuButton(string $text, string $url): bool
	{
		$botToken = config('services.telegram.bot_token');

		if (empty($botToken)) {
			return false;
		}

		$url = trim($url);
		$text = trim($text);

		if ($url === '' || $text === '') {
			return false;
		}

		$apiUrl = "https://api.telegram.org/bot{$botToken}/setChatMenuButton";

		$response = Http::post($apiUrl, [
			'menu_button' => [
				'type' => 'web_app',
				'text' => $text,
				'web_app' => [
					'url' => $url,
				],
			],
		]);

		return $response->successful();
	}
}

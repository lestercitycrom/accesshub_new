<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use Illuminate\Support\Facades\Http;

final class TelegramClient
{
	public function sendMessage(string $chatId, string $text): bool
	{
		$botToken = config('services.telegram.bot_token');

		if (empty($botToken)) {
			return false;
		}

		$url = "https://api.telegram.org/bot{$botToken}/sendMessage";

		$response = Http::post($url, [
			'chat_id' => $chatId,
			'text' => $text,
			'parse_mode' => 'HTML',
		]);

		return $response->successful();
	}
}
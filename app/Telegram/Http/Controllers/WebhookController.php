<?php

declare(strict_types=1);

namespace App\Telegram\Http\Controllers;

use App\Telegram\DTO\IncomingUpdate;
use App\Telegram\Services\BotDispatcher;
use App\Telegram\Services\TelegramClient;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class WebhookController
{
	public function __construct(
		private readonly BotDispatcher $dispatcher,
		private readonly TelegramClient $telegramClient,
	) {}

	public function handle(Request $request)
	{
		try {
			$update = $this->parseUpdate($request->all());

			if (!$update) {
				return response()->json(['status' => 'ignored'], 200);
			}

			// Auto-register Telegram user
			$this->upsertTelegramUser($update);

			// Dispatch message
			$responseText = $this->dispatcher->dispatch($update);

			// Send response back to Telegram
			if ($update->chatId && $responseText) {
				$this->telegramClient->sendMessage($update->chatId, $responseText);
			}

			return response()->json(['status' => 'ok'], 200);
		} catch (\Throwable $e) {
			// Log error and return error response
			\Log::error('Webhook error: ' . $e->getMessage());
			return response()->json(['status' => 'error'], 500);
		}
	}

	private function parseUpdate(array $data): ?IncomingUpdate
	{
		$message = $data['message'] ?? null;

		if (!$message) {
			return null;
		}

		$chat = $message['chat'] ?? null;
		$from = $message['from'] ?? null;
		$text = $message['text'] ?? null;
		$webAppData = $message['web_app_data']['data'] ?? null;

		if (!$chat || !$from) {
			return null;
		}

		return new IncomingUpdate(
			updateId: (string) ($data['update_id'] ?? ''),
			chatId: (string) $chat['id'],
			telegramId: (string) $from['id'],
			username: $from['username'] ?? null,
			firstName: $from['first_name'] ?? null,
			lastName: $from['last_name'] ?? null,
			text: $text,
			webAppData: $webAppData,
		);
	}

	private function upsertTelegramUser(IncomingUpdate $update): void
	{
		$telegramId = (int) $update->telegramId;

		$user = TelegramUser::query()
			->where('telegram_id', $telegramId)
			->first();

		if ($user) {
			$user->update([
				'username' => $update->username,
				'first_name' => $update->firstName,
				'last_name' => $update->lastName,
			]);

			return;
		}

		TelegramUser::query()->create([
			'telegram_id' => $telegramId,
			'username' => $update->username,
			'first_name' => $update->firstName,
			'last_name' => $update->lastName,
			'role' => \App\Domain\Telegram\Enums\TelegramRole::OPERATOR,
			'is_active' => false,
		]);
	}
}

<?php

declare(strict_types=1);

namespace App\Telegram\Http\Controllers;

use App\Models\ServerError;
use App\Telegram\DTO\IncomingUpdate;
use App\Telegram\Services\BotDispatcher;
use App\Telegram\Services\TelegramClient;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class WebhookController
{
	private const USER_MESSAGE = 'Произошла внутренняя ошибка сервера. Пожалуйста, сообщите администратору номер обращения: #%d.';

	public function __construct(
		private readonly BotDispatcher $dispatcher,
		private readonly TelegramClient $telegramClient,
	) {}

	public function handle(Request $request): JsonResponse
	{
		$chatId = null;
		$telegramId = null;

		try {
			$update = $this->parseUpdate($request->all());

			if (!$update) {
				return response()->json(['status' => 'ignored'], 200);
			}

			$chatId = $update->chatId;
			$telegramId = (int) $update->telegramId;

			// Auto-register Telegram user
			$this->upsertTelegramUser($update);

			// Dispatch message
			$responseText = $this->dispatcher->dispatch($update);

			// Send response back to Telegram
			if ($chatId && $responseText) {
				$this->telegramClient->sendMessage($chatId, $responseText);
			}

			return response()->json(['status' => 'ok'], 200);
		} catch (\Throwable $e) {
			$telegramIdFromRequest = $this->extractTelegramIdFromRequest($request->all());
			$chatIdFromRequest = $this->extractChatIdFromRequest($request->all());

			$error = ServerError::log('webhook', $e, $telegramId ?? $telegramIdFromRequest, null, [
				'update_id' => $request->input('update_id'),
				'has_message' => $request->has('message'),
				'has_web_app_data' => $request->has('message.web_app_data'),
			]);

			\Log::error('Webhook error: ' . $e->getMessage(), ['server_error_id' => $error->id]);

			$userMessage = sprintf(self::USER_MESSAGE, $error->id);
			$sendChatId = $chatId ?? $chatIdFromRequest;
			if ($sendChatId) {
				try {
					$this->telegramClient->sendMessage($sendChatId, $userMessage);
				} catch (\Throwable) {
					// ignore send failure
				}
			}

			return response()->json(['status' => 'ok'], 200);
		}
	}

	private function extractTelegramIdFromRequest(array $data): ?int
	{
		$from = $data['message']['from'] ?? null;

		return $from ? (int) ($from['id'] ?? null) : null;
	}

	private function extractChatIdFromRequest(array $data): ?string
	{
		$chat = $data['message']['chat'] ?? null;

		return $chat ? (string) ($chat['id'] ?? null) : null;
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

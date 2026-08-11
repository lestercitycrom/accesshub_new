<?php

declare(strict_types=1);

namespace App\Telegram\Http\Controllers;

use App\Models\ServerError;
use App\Telegram\DTO\IncomingUpdate;
use App\Telegram\DTO\OutgoingMessage;
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
		// Authenticity check: when a webhook secret is configured, every update
		// must carry Telegram's secret-token header. Empty secret = disabled
		// (so deploying this code is inert until the secret is set + webhook
		// re-registered together). See docs/delivery_audit.md A1.
		$expectedSecret = (string) config('services.telegram.webhook_secret', '');
		if ($expectedSecret !== '') {
			$provided = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
			if (!hash_equals($expectedSecret, $provided)) {
				return response()->json(['status' => 'forbidden'], 403);
			}
		}

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
			$response = $this->dispatcher->dispatch($update);

			// Send response back to Telegram
			if ($chatId && $response instanceof OutgoingMessage) {
				$this->telegramClient->sendMessage($chatId, $response->text, replyMarkup: $response->replyMarkup);
			} elseif ($chatId && is_string($response) && $response !== '') {
				$this->telegramClient->sendMessage($chatId, $response);
			}

			return response()->json(['status' => 'ok'], 200);
		} catch (\Throwable $e) {
			$telegramIdFromRequest = $this->extractTelegramIdFromRequest($request->all());
			$chatIdFromRequest = $this->extractChatIdFromRequest($request->all());

			$error = ServerError::log('webhook', $e, $telegramId ?? $telegramIdFromRequest, null, [
				'update_id' => $request->input('update_id'),
				'has_message' => $request->has('message'),
				'has_callback_query' => $request->has('callback_query'),
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
		$from = $data['message']['from'] ?? $data['callback_query']['from'] ?? null;

		return $from ? (int) ($from['id'] ?? null) : null;
	}

	private function extractChatIdFromRequest(array $data): ?string
	{
		$chat = $data['message']['chat'] ?? $data['callback_query']['message']['chat'] ?? null;

		return $chat ? (string) ($chat['id'] ?? null) : null;
	}

	private function parseUpdate(array $data): ?IncomingUpdate
	{
		if (isset($data['message'])) {
			$message = $data['message'];
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

		if (isset($data['callback_query'])) {
			$callback = $data['callback_query'];
			$from = $callback['from'] ?? null;
			$chat = $callback['message']['chat'] ?? null;
			$callbackQueryId = $callback['id'] ?? null;
			$callbackData = $callback['data'] ?? null;

			if (!$from || !$callbackQueryId || !$callbackData) {
				return null;
			}

			return new IncomingUpdate(
				updateId: (string) ($data['update_id'] ?? ''),
				chatId: $chat ? (string) $chat['id'] : null,
				telegramId: (string) $from['id'],
				username: $from['username'] ?? null,
				firstName: $from['first_name'] ?? null,
				lastName: $from['last_name'] ?? null,
				text: null,
				webAppData: null,
				callbackQueryId: (string) $callbackQueryId,
				callbackData: (string) $callbackData,
			);
		}

		return null;
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

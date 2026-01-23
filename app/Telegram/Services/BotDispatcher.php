<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use App\Telegram\DTO\IncomingUpdate;
use App\Telegram\DTO\IncomingIssueRequest;
use App\Telegram\Services\Parsers\TextIssueParser;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Issuance\DTO\IssuanceResult;
use App\Domain\Accounts\Services\AccountStatusService;
use App\Domain\Accounts\Enums\AccountStatus;

final class BotDispatcher
{
	public function __construct(
		private readonly TextIssueParser $textIssueParser,
		private readonly IssueService $issueService,
		private readonly AccountStatusService $accountStatusService,
		private readonly TelegramClient $telegramClient,
	) {}

	public function dispatch(IncomingUpdate $incoming): ?string
	{
		$request = $this->parseIncomingRequest($incoming);

		if (!$request) {
			return 'Неверный формат запроса. Используйте: order_id\\nигровая_платформа x2';
		}

		$result = $this->issueService->issue(
			telegramId: $request->telegramId,
			orderId: $request->orderId,
			game: $request->game,
			platform: $request->platform,
			qty: $request->qty,
		);

		if (!$result->ok()) {
			return 'Ошибка выдачи: ' . ($result->message() ?? 'Неизвестная ошибка');
		}

		return $this->formatSuccessMessage($result);
	}

	private function parseIncomingRequest(IncomingUpdate $incoming): ?IncomingIssueRequest
	{
		// Check for WebApp data first
		if ($incoming->webAppData) {
			return $this->parseWebAppData($incoming);
		}

		// Fall back to text parsing
		if ($incoming->text === null) {
			return null;
		}

		return $this->textIssueParser->parse($incoming->chatId, $incoming->telegramId, $incoming->text);
	}

	private function parseWebAppData(IncomingUpdate $incoming): ?IncomingIssueRequest
	{
		try {
			$data = json_decode($incoming->webAppData, true, JSON_THROW_ON_ERROR);

			if (!isset($data['action']) || $data['action'] !== 'issue') {
				return null;
			}

			$payload = $data['payload'] ?? [];

			return new IncomingIssueRequest(
				$incoming->chatId,
				(int) $incoming->telegramId,
				$payload['order_id'] ?? '',
				$payload['game'] ?? '',
				$payload['platform'] ?? '',
				max(1, (int) ($payload['qty'] ?? 1))
			);
		} catch (\JsonException) {
			return null;
		}
	}

	private function formatSuccessMessage(IssuanceResult $result): string
	{
		return "✅ Аккаунт выдан!\n\n" .
			"🎮 Логин: <code>{$result->login}</code>\n" .
			"🔑 Пароль: <code>{$result->password}</code>\n\n" .
			"⚠️ Не передавайте данные третьим лицам!";
	}
}
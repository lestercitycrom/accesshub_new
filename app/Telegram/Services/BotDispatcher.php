<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use App\Domain\Issuance\DTO\IssuanceResult;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Accounts\Services\AccountStatusService;
use App\Telegram\DTO\IncomingIssueRequest;
use App\Telegram\DTO\IncomingUpdate;
use App\Telegram\Services\Parsers\TextIssueParser;

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
			// Use real newlines (double quotes) for Telegram rendering.
			return "Неверный формат запроса.\n\nИспользуйте:\n<code>order_id</code>\n<code>игровая_платформа x2</code>";
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
		// Check for WebApp data first.
		if ($incoming->webAppData) {
			return $this->parseWebAppData($incoming);
		}

		// Fall back to text parsing.
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
		// Build message from IssuanceResult items.
		if (!$result->ok()) {
			return (string) ($result->message() ?? 'Error.');
		}

		$items = $result->items;

		if (count($items) === 0) {
			return '✅ OK';
		}

		if (count($items) === 1) {
			return
				"✅ Выдано:\n\n" .
				"🎮 Логин: <code>{$items[0]['login']}</code>\n" .
				"🔑 Пароль: <code>{$items[0]['password']}</code>\n";
		}

		$lines = [];
		$lines[] = '✅ Выдано (x' . count($items) . ')';

		foreach ($items as $index => $item) {
			$lines[] =
				"\n#" . ($index + 1) . "\n" .
				"🎮 Логин: <code>{$item['login']}</code>\n" .
				"🔑 Пароль: <code>{$item['password']}</code>\n";
		}

		return implode('', $lines);
	}
}

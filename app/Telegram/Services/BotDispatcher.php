<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use App\Domain\Issuance\Services\IssueService;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Accounts\Services\AccountStatusService;
use App\Telegram\DTO\IncomingIssueRequest;
use App\Telegram\DTO\IncomingUpdate;
use App\Telegram\Services\Parsers\TextIssueParser;
use App\Telegram\Services\IssueMessageFormatter;
use App\Domain\Telegram\Models\TelegramUser;

final class BotDispatcher
{
	public function __construct(
		private readonly TextIssueParser $textIssueParser,
		private readonly IssueService $issueService,
		private readonly AccountStatusService $accountStatusService,
		private readonly TelegramClient $telegramClient,
		private readonly IssueMessageFormatter $messageFormatter,
	) {}

	public function dispatch(IncomingUpdate $incoming): ?string
	{
		$telegramId = (int) $incoming->telegramId;
		$user = TelegramUser::query()->where('telegram_id', $telegramId)->first();

		if ($user === null || $user->is_active !== true) {
			return 'Ваш аккаунт на модерации. Доступ будет открыт после подтверждения админом.';
		}

		if ($incoming->webAppData) {
			$result = $this->handleWebAppAction($incoming);
			if ($result !== null) {
				return $result;
			}
		}

		if ($incoming->text !== null) {
			$text = trim($incoming->text);
			if ($text !== '' && str_starts_with($text, '/start')) {
				return "Бот готов к работе.\n\nФормат выдачи:\n<code>order_id</code>\n<code>игровая_платформа</code>\n\nЕсли нужно 2 аккаунта, укажи <code>x2</code> в конце второй строки.";
			}
		}

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

		return $this->messageFormatter->format($result);
	}

	private function handleWebAppAction(IncomingUpdate $incoming): ?string
	{
		try {
			$data = json_decode($incoming->webAppData ?? '', true, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			return null;
		}

		if (!is_array($data) || !isset($data['action'])) {
			return null;
		}

		$action = (string) $data['action'];
		$payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

		if ($action === 'issue') {
			return null;
		}

		$telegramId = (int) $incoming->telegramId;

		if ($action === 'mark_problem') {
			$accountId = (int) ($payload['account_id'] ?? 0);
			$reason = (string) ($payload['reason'] ?? '');

			if ($accountId <= 0 || $reason === '') {
				return 'Неверные данные.';
			}

			$this->accountStatusService->markProblem($accountId, $telegramId, $reason, [
				'source' => 'webapp',
			]);

			$issuance = Issuance::query()
				->where('account_id', $accountId)
				->where('telegram_id', $telegramId)
				->orderByDesc('issued_at')
				->first();

			if ($issuance === null) {
				return sprintf('Проблема сохранена: %s (аккаунт #%d).', $reason, $accountId);
			}

			$replacement = $this->issueService->issue(
				telegramId: $telegramId,
				orderId: (string) $issuance->order_id,
				game: (string) $issuance->game,
				platform: (string) $issuance->platform,
				qty: 1,
			);

			if ($replacement->ok() !== true) {
				return sprintf(
					'Проблема сохранена: %s (аккаунт #%d). Замена не выдана: %s',
					$reason,
					$accountId,
					(string) ($replacement->message() ?? 'Ошибка.')
				);
			}

			return "Проблема сохранена. Выдана замена:\n\n" . $this->messageFormatter->format($replacement);
		}

		if ($action === 'update_password') {
			$accountId = (int) ($payload['account_id'] ?? 0);
			$password = trim((string) ($payload['password'] ?? ''));

			if ($accountId <= 0 || $password === '') {
				return 'Неверные данные.';
			}

			$this->accountStatusService->updatePassword($accountId, $password, $telegramId, [
				'source' => 'webapp',
			]);

			return sprintf('Пароль обновлён (аккаунт #%d).', $accountId);
		}

		if ($action === 'recover_stolen') {
			$accountId = (int) ($payload['account_id'] ?? 0);
			$password = trim((string) ($payload['password'] ?? ''));

			if ($accountId <= 0 || $password === '') {
				return 'Неверные данные.';
			}

			$this->accountStatusService->recoverStolen($accountId, $password, $telegramId, [
				'source' => 'webapp',
			]);

			return sprintf('STOLEN восстановлен (аккаунт #%d).', $accountId);
		}

		if ($action === 'postpone_stolen') {
			$accountId = (int) ($payload['account_id'] ?? 0);

			if ($accountId <= 0) {
				return 'Неверные данные.';
			}

			$ok = $this->accountStatusService->extendDeadline($accountId, 1, $telegramId, [
				'source' => 'webapp',
				'action' => 'postpone',
			]);

			return $ok
				? sprintf('STOLEN перенесён на 1 день (аккаунт #%d).', $accountId)
				: 'Не удалось перенести.';
		}

		return null;
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
}

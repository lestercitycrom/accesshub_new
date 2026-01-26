<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Accounts\Services\AccountStatusService;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Telegram\Services\IssueMessageFormatter;
use App\Telegram\Services\TelegramClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProblemController
{
	public function __construct(
		private readonly AccountStatusService $accountStatusService,
		private readonly IssueService $issueService,
		private readonly TelegramClient $telegramClient,
		private readonly IssueMessageFormatter $messageFormatter,
	) {}

	public function __invoke(Request $request): JsonResponse
	{
		$telegramId = (int) $request->session()->get('webapp.telegram_id', 0);

		if ($telegramId <= 0) {
			return response()->json(['error' => 'Не инициализировано.'], 403);
		}

		$accountId = (int) $request->input('account_id', 0);
		$reason = trim((string) $request->input('reason', ''));

		if ($accountId <= 0 || $reason === '') {
			return response()->json(['error' => 'Неверные данные.'], 422);
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
			$message = sprintf('Проблема сохранена: %s (аккаунт #%d).', $reason, $accountId);
			$this->telegramClient->sendMessage((string) $telegramId, $message);
			return response()->json(['ok' => true, 'message' => 'Проблема сохранена. Ответ в чате.']);
		}

		$replacement = $this->issueService->issue(
			telegramId: $telegramId,
			orderId: (string) $issuance->order_id,
			game: (string) $issuance->game,
			platform: (string) $issuance->platform,
			qty: 1,
		);

		if ($replacement->ok() !== true) {
			$message = sprintf(
				'Проблема сохранена: %s (аккаунт #%d). Замена не выдана: %s',
				$reason,
				$accountId,
				(string) ($replacement->message() ?? 'Ошибка.')
			);
			$this->telegramClient->sendMessage((string) $telegramId, $message);
			return response()->json(['ok' => false, 'message' => 'Проблема сохранена. Замена не выдана.']);
		}

		$message = "Проблема сохранена. Выдана замена:\n\n" . $this->messageFormatter->format($replacement);
		$this->telegramClient->sendMessage((string) $telegramId, $message);

		return response()->json(['ok' => true, 'message' => 'Проблема сохранена. Замена отправлена в чат.']);
	}
}

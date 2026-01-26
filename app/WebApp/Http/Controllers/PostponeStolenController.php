<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Accounts\Services\AccountStatusService;
use App\Telegram\Services\TelegramClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostponeStolenController
{
	public function __construct(
		private readonly AccountStatusService $accountStatusService,
		private readonly TelegramClient $telegramClient,
	) {}

	public function __invoke(Request $request): JsonResponse
	{
		$telegramId = (int) $request->session()->get('webapp.telegram_id', 0);

		if ($telegramId <= 0) {
			return response()->json(['error' => 'Не инициализировано.'], 403);
		}

		$accountId = (int) $request->input('account_id', 0);

		if ($accountId <= 0) {
			return response()->json(['error' => 'Неверные данные.'], 422);
		}

		$ok = $this->accountStatusService->extendDeadline($accountId, 1, $telegramId, [
			'source' => 'webapp',
			'action' => 'postpone',
		]);

		if ($ok) {
			$this->telegramClient->sendMessage((string) $telegramId, sprintf('STOLEN перенесён на 1 день (аккаунт #%d).', $accountId));
			return response()->json(['ok' => true, 'message' => 'Дедлайн перенесён. Ответ в чате.']);
		}

		return response()->json(['ok' => false, 'message' => 'Не удалось перенести.']);
	}
}

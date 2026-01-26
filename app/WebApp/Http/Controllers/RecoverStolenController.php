<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Accounts\Services\AccountStatusService;
use App\Telegram\Services\TelegramClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RecoverStolenController
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
		$password = trim((string) $request->input('password', ''));

		if ($accountId <= 0 || $password === '') {
			return response()->json(['error' => 'Неверные данные.'], 422);
		}

		$this->accountStatusService->recoverStolen($accountId, $password, $telegramId, [
			'source' => 'webapp',
		]);

		$this->telegramClient->sendMessage((string) $telegramId, sprintf('STOLEN восстановлен (аккаунт #%d).', $accountId));

		return response()->json(['ok' => true, 'message' => 'STOLEN восстановлен. Ответ в чате.']);
	}
}

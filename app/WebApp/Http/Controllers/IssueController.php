<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Issuance\Services\IssueService;
use App\Domain\Settings\Services\SettingsService;
use App\Telegram\Services\IssueMessageFormatter;
use App\Telegram\Services\TelegramClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class IssueController
{
	public function __construct(
		private readonly IssueService $issueService,
		private readonly TelegramClient $telegramClient,
		private readonly IssueMessageFormatter $messageFormatter,
		private readonly SettingsService $settings,
	) {}

	public function __invoke(Request $request): JsonResponse
	{
		$telegramId = (int) $request->session()->get('webapp.telegram_id', 0);

		if ($telegramId <= 0) {
			return response()->json(['error' => 'Не инициализировано.'], 403);
		}

		$orderId = trim((string) $request->input('order_id', ''));
		$platform = trim((string) $request->input('platform', ''));
		$game = trim((string) $request->input('game', ''));
		$qtyRaw = (int) $request->input('qty', 1);
		$qty = max(1, min(2, $qtyRaw));

		if ($orderId === '' || $platform === '' || $game === '') {
			return response()->json(['error' => 'Заполните все поля.'], 422);
		}

		$result = $this->issueService->issue(
			telegramId: $telegramId,
			orderId: $orderId,
			game: $game,
			platform: $platform,
			qty: $qty,
		);

		if (!$result->ok()) {
			return response()->json([
				'ok' => false,
				'error' => $result->message() ?? 'Ошибка выдачи.',
			], 422);
		}

		$deliveryMode = (string) ($this->settings->get('webapp_issue_delivery') ?? 'both');
		if (!in_array($deliveryMode, ['webapp', 'chat', 'both'], true)) {
			$deliveryMode = 'both';
		}

		$message = $this->messageFormatter->format($result);
		$sendToChat = $deliveryMode === 'chat' || $deliveryMode === 'both';
		$showInWebapp = $deliveryMode === 'webapp' || $deliveryMode === 'both';

		if ($sendToChat) {
			try {
				$this->telegramClient->sendMessage((string) $telegramId, $message);
			} catch (\Throwable) {
				// Ignore chat failures for webapp response.
			}
		}

		return response()->json([
			'ok' => true,
			'message' => $showInWebapp ? $message : 'Отправлено в чат.',
			'items' => $showInWebapp ? $result->items : [],
			'show_in_webapp' => $showInWebapp,
			'sent_to_chat' => $sendToChat,
		]);
	}
}

<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Accounts\Services\AccountStatusService;
use App\Telegram\DTO\IncomingIssueRequest;
use App\Telegram\DTO\IncomingUpdate;
use App\Telegram\Services\Parsers\TextIssueParser;
use App\Telegram\Services\IssueMessageFormatter;
use App\Domain\Telegram\Models\TelegramUser;
use App\Domain\Telegram\Enums\TelegramRole;
use App\WebApp\Services\WebAppTokenService;

final class BotDispatcher
{
	public function __construct(
		private readonly TextIssueParser $textIssueParser,
		private readonly IssueService $issueService,
		private readonly AccountStatusService $accountStatusService,
		private readonly TelegramClient $telegramClient,
		private readonly IssueMessageFormatter $messageFormatter,
		private readonly WebAppTokenService $tokenService,
		private readonly DeliveryOrderService $deliveryOrders,
		private readonly IssuanceStaffNotifier $staffNotifier,
	) {}

	public function dispatch(IncomingUpdate $incoming): ?string
	{
		$telegramId = (int) $incoming->telegramId;
		$user = TelegramUser::query()->where('telegram_id', $telegramId)->first();

		if ($user === null || $user->is_active !== true) {
			return 'Ваш аккаунт на модерации. Доступ будет открыт после подтверждения админом.';
		}

		if ($incoming->callbackData !== null) {
			return $this->handleCallback($incoming);
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

			if ($text === '/link') {
				$token = $this->tokenService->generate($telegramId);
				$url = rtrim(config('app.url'), '/') . '/webapp/auth/' . $token;
				$this->telegramClient->sendMessage(
					$incoming->chatId,
					"Ссылка для входа в браузере (действует 15 минут):\n{$url}",
					disableLinkPreview: true,
				);
				return null;
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

		$this->staffNotifier->notify(
			orderId: $request->orderId,
			game: $request->game,
			platform: $request->platform,
			operatorTelegramId: $telegramId,
			issuedItems: $result->items,
		);

		return $this->messageFormatter->format($result);
	}

	private function handleCallback(IncomingUpdate $incoming): ?string
	{
		if ($incoming->callbackQueryId === null) {
			return null;
		}

		$data = (string) $incoming->callbackData;

		if (!str_starts_with($data, 'delivery:')) {
			$this->telegramClient->answerCallbackQuery($incoming->callbackQueryId, 'Unknown action.', true);
			return null;
		}

		$parts = explode(':', $data);
		$action = $parts[1] ?? '';
		$ref = (string) ($parts[2] ?? '');

		if ($ref === '') {
			$this->telegramClient->answerCallbackQuery($incoming->callbackQueryId, 'Order is not specified.', true);
			return null;
		}

		// P.4: target is the order (numeric id) or a game item ("i<itemId>").
		if (str_starts_with($ref, 'i')) {
			$itemId = (int) substr($ref, 1);
			$holder = $itemId > 0 ? \App\Delivery\Models\DeliveryOrderItem::query()->find($itemId) : null;
		} else {
			$orderId = (int) $ref;
			$holder = $orderId > 0 ? DeliveryOrder::query()->find($orderId) : null;
		}

		if ($holder === null) {
			$this->telegramClient->answerCallbackQuery($incoming->callbackQueryId, 'Order not found.', true);
			return null;
		}

		$telegramId = (int) $incoming->telegramId;

		if (!in_array($action, ['connecting', 'connected', 'failed', 'extra'], true)) {
			$this->telegramClient->answerCallbackQuery($incoming->callbackQueryId, 'Unknown delivery action.', true);
			return null;
		}

		$result = match ($action) {
			'connecting' => $this->deliveryOrders->markOperatorConnecting($holder, $telegramId),
			'connected' => $this->deliveryOrders->markConnected($holder, $telegramId),
			'failed' => $this->deliveryOrders->markConnectionFailed($holder, $telegramId, 'telegram_callback'),
			'extra' => $this->deliveryOrders->grantExtraAttempts($holder, $telegramId, (int) ($parts[3] ?? 1)),
		};

		if ($result->failed()) {
			$this->telegramClient->answerCallbackQuery($incoming->callbackQueryId, $result->message() ?? 'Действие недоступно.', true);
			return null;
		}

		$this->telegramClient->answerCallbackQuery($incoming->callbackQueryId, 'Order updated.');

		return null;
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
				allowRepeatOrder: true,
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

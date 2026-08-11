<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Facades\Log;

final class IssuanceAdminNotifier
{
	public function __construct(
		private readonly TelegramClient $telegramClient,
	) {}

	public function notify(string $orderId, string $game, string $platform, int $operatorTelegramId): void
	{
		$operator = TelegramUser::query()
			->where('telegram_id', $operatorTelegramId)
			->first();

		$operatorName = $operator?->username
			? '@' . $operator->username
			: ($operator?->first_name ?? (string) $operatorTelegramId);

		$message = "{$game} ({$platform})\n"
			. "{$orderId}\n"
			. "The Buyer has paid for the order\n"
			. "👤 {$operatorName}";

		$admins = TelegramUser::query()
			->where('role', TelegramRole::ADMIN)
			->where('is_active', true)
			->where('telegram_id', '<>', $operatorTelegramId)
			->get();

		foreach ($admins as $admin) {
			try {
				$this->telegramClient->sendMessage((string) $admin->telegram_id, $message);
			} catch (\Throwable $exception) {
				Log::warning('Failed to notify admin about issuance.', [
					'admin_telegram_id' => $admin->telegram_id,
					'operator_telegram_id' => $operatorTelegramId,
					'order_id' => $orderId,
					'error' => $exception->getMessage(),
				]);
			}
		}
	}
}

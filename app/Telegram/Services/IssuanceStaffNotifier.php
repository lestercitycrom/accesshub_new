<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Facades\Log;

final class IssuanceStaffNotifier
{
	public function __construct(
		private readonly TelegramClient $telegramClient,
	) {}

	/**
	 * @param array<int, array{source_label?:?string}> $issuedItems
	 */
	public function notify(
		string $orderId,
		string $game,
		string $platform,
		int $operatorTelegramId,
		array $issuedItems,
	): void {
		$operator = TelegramUser::query()
			->where('telegram_id', $operatorTelegramId)
			->first();

		$operatorName = $operator?->username
			? '@'.$operator->username
			: ($operator?->first_name ?? (string) $operatorTelegramId);

		$sourceLabels = array_values(array_unique(array_filter(array_map(
			static fn (array $item): ?string => isset($item['source_label']) && trim((string) $item['source_label']) !== ''
				? strtoupper(trim((string) $item['source_label']))
				: null,
			$issuedItems,
		))));

		$lines = [
			'✅ <b>Аккаунт выдан</b>',
			'➖➖➖➖➖➖➖➖➖➖',
			'📋 Заказ: <code>'.$this->escape($orderId).'</code>',
			'🕹 Игра: <b>'.$this->escape($game).'</b>',
			'🎮 Платформа: <b>'.$this->escape($platform).'</b>',
			'🔢 Количество: <b>'.max(1, count($issuedItems)).'</b>',
		];

		if ($sourceLabels !== []) {
			$lines[] = '🏷 Канал продаж: <b>'.$this->escape(implode(', ', $sourceLabels)).'</b>';
		}

		$lines[] = '👤 Выдал: '.$this->escape($operatorName);

		$recipients = TelegramUser::query()
			->where('is_active', true)
			->whereIn('role', [
				TelegramRole::OPERATOR->value,
				TelegramRole::DELIVERY_OPERATOR->value,
				TelegramRole::ADMIN->value,
			])
			->where('telegram_id', '<>', $operatorTelegramId)
			->orderBy('id')
			->get();

		$message = implode("\n", $lines);

		foreach ($recipients as $recipient) {
			try {
				$this->telegramClient->sendMessage(
					(string) $recipient->telegram_id,
					$message,
					disableLinkPreview: true,
				);
			} catch (\Throwable $exception) {
				Log::warning('Failed to notify staff about issuance.', [
					'recipient_telegram_id' => $recipient->telegram_id,
					'operator_telegram_id' => $operatorTelegramId,
					'order_id' => $orderId,
					'error' => $exception->getMessage(),
				]);
			}
		}
	}

	private function escape(mixed $value): string
	{
		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}

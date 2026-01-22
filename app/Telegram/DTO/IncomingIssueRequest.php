<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

final class IncomingIssueRequest
{
	public function __construct(
		public readonly string $chatId,
		public readonly int $telegramId,
		public readonly string $orderId,
		public readonly string $game,
		public readonly string $platform,
		public readonly int $qty,
	) {}
}
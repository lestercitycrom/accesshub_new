<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

final class IncomingUpdate
{
	public function __construct(
		public readonly string $updateId,
		public readonly ?string $chatId,
		public readonly ?string $telegramId,
		public readonly ?string $username,
		public readonly ?string $firstName,
		public readonly ?string $lastName,
		public readonly ?string $text,
		public readonly ?string $webAppData,
	) {}
}
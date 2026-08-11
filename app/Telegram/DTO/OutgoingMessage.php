<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

final readonly class OutgoingMessage
{
	public function __construct(
		public string $text,
		public ?array $replyMarkup = null,
	) {}
}

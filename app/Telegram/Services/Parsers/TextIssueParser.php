<?php

declare(strict_types=1);

namespace App\Telegram\Services\Parsers;

use App\Telegram\DTO\IncomingIssueRequest;

final class TextIssueParser
{
	public function parse(string $chatId, string $telegramId, string $text): ?IncomingIssueRequest
	{
		$lines = array_filter(array_map('trim', explode("\n", $text)));

		// Format: "2 строки + x2"
		// First line: order_id
		// Second line: "game platform x2" or just "game platform"
		if (count($lines) < 2) {
			return null;
		}

		$orderId = $lines[0];

		// Parse second line: "game platform x2" or "game platform"
		$secondLineParts = explode(' ', $lines[1]);
		if (count($secondLineParts) < 2) {
			return null;
		}

		$game = $secondLineParts[0];
		$platform = $secondLineParts[1];

		// Check for qty (x2, x3, etc.)
		$qty = 1; // default
		if (isset($secondLineParts[2])) {
			$qtyPart = $secondLineParts[2];
			if (preg_match('/^x(\d+)$/', $qtyPart, $matches)) {
				$qty = (int) $matches[1];
			}
		}

		return new IncomingIssueRequest(
			$chatId,
			(int) $telegramId,
			$orderId,
			$game,
			$platform,
			$qty
		);
	}
}
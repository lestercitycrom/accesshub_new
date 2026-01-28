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
		$secondLine = trim($lines[1]);

		// Parse second line: "game platform x2" or "game platform"
		// Platform is always the last word before optional x2/x3/etc
		// Game is everything before the platform
		
		// Check for qty pattern (x2, x3, etc.) at the end
		$qty = 1; // default
		$qtyPattern = '/\s+x(\d+)$/i';
		if (preg_match($qtyPattern, $secondLine, $qtyMatches)) {
			$qty = (int) $qtyMatches[1];
			// Remove qty part from line
			$secondLine = preg_replace($qtyPattern, '', $secondLine);
		}

		// Split by last space to separate game and platform
		$lastSpacePos = strrpos($secondLine, ' ');
		if ($lastSpacePos === false) {
			return null; // Need at least game and platform separated by space
		}

		$game = trim(substr($secondLine, 0, $lastSpacePos));
		$platform = trim(substr($secondLine, $lastSpacePos + 1));

		if ($game === '' || $platform === '') {
			return null;
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
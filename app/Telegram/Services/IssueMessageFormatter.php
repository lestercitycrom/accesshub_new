<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use App\Domain\Issuance\DTO\IssuanceResult;

final class IssueMessageFormatter
{
	public function format(IssuanceResult $result): string
	{
		if (!$result->ok()) {
			return (string) ($result->message() ?? 'Error.');
		}

		$items = $result->items;

		if (count($items) === 0) {
			return '✅ OK';
		}

		$orderLine = $result->orderId ? "📋 Заказ: <code>{$result->orderId}</code>\n" : '';
		$gameLine = $result->game ? "🕹 Игра: {$result->game}" . ($result->platform ? " ({$result->platform})" : '') . "\n" : '';

		if (count($items) === 1) {
			$message = "✅ Выдано:\n\n" .
				$orderLine .
				$gameLine .
				"🎮 Login: <code>{$items[0]['login']}</code>\n" .
				"🔑 Password: <code>{$items[0]['password']}</code>\n";

			if (($items[0]['source_label'] ?? null) === 'allkeyshop') {
				$message .= "🏷 ALLKEYSHOP\n";
			}

			if (!empty($items[0]['comment'])) {
				$message .= "\n💬 Комментарий: {$items[0]['comment']}\n";
			}

			return $message;
		}

		$lines = [];
		$lines[] = '✅ Выдано (x' . count($items) . ")\n" . $orderLine . $gameLine;

		foreach ($items as $index => $item) {
			$itemLines = [
				"\n#" . ($index + 1) . "\n",
				"🎮 Login: <code>{$item['login']}</code>\n",
				"🔑 Password: <code>{$item['password']}</code>\n",
			];

			if (($item['source_label'] ?? null) === 'allkeyshop') {
				$itemLines[] = "🏷 ALLKEYSHOP\n";
			}
			
			if (!empty($item['comment'])) {
				$itemLines[] = "💬 Комментарий: {$item['comment']}\n";
			}
			
			$lines[] = implode('', $itemLines);
		}

		return implode('', $lines);
	}

	public function copyCredentialsKeyboard(IssuanceResult $result): ?array
	{
		if (!$result->ok()) {
			return null;
		}

		$rows = [];
		$itemCount = count($result->items);

		foreach ($result->items as $index => $item) {
			$text = "Login: {$item['login']}\nPassword: {$item['password']}";

			// Telegram accepts 1-256 characters in CopyTextButton.text. Never
			// truncate credentials: omit the button when the pair is too long.
			$length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
			if ($length > 256) {
				continue;
			}

			$rows[] = [[
				'text' => $itemCount === 1
					? '📋 Copy credentials'
					: '📋 Copy credentials #' . ($index + 1),
				'copy_text' => [
					'text' => $text,
				],
			]];
		}

		return $rows === [] ? null : ['inline_keyboard' => $rows];
	}
}

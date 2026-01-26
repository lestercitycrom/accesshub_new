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

		if (count($items) === 1) {
			return
				"✅ Выдано:\n\n" .
				"🎮 Логин: <code>{$items[0]['login']}</code>\n" .
				"🔑 Пароль: <code>{$items[0]['password']}</code>\n";
		}

		$lines = [];
		$lines[] = '✅ Выдано (x' . count($items) . ')';

		foreach ($items as $index => $item) {
			$lines[] =
				"\n#" . ($index + 1) . "\n" .
				"🎮 Логин: <code>{$item['login']}</code>\n" .
				"🔑 Пароль: <code>{$item['password']}</code>\n";
		}

		return implode('', $lines);
	}
}

<?php

declare(strict_types=1);

namespace Tests\Support;

final class TelegramInitDataFactory
{
	/**
	 * @param array<string, mixed> $user
	 */
	public static function make(string $botToken, array $user, ?int $authDate = null): string
	{
		$data = [
			'auth_date' => (string) ($authDate ?? time()),
			'query_id' => 'AAEAAAEAAA',
			'user' => json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];

		ksort($data);

		$parts = [];
		foreach ($data as $k => $v) {
			$parts[] = $k . '=' . $v;
		}

		$checkString = implode("\n", $parts);

		$secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
		$hash = hash_hmac('sha256', $checkString, $secretKey);

		$data['hash'] = $hash;

		return http_build_query($data);
	}
}
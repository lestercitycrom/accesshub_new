<?php

declare(strict_types=1);

namespace App\WebApp\Services;

final class TelegramInitDataVerifier
{
	/**
	 * @return array{ok: bool, telegram_id?: int, user?: array<string, mixed>, error?: string}
	 */
	public function verify(string $initData, string $botToken, int $maxAgeSeconds): array
	{
		$initData = trim($initData);

		if ($initData === '') {
			return ['ok' => false, 'error' => 'Empty initData.'];
		}

		$params = [];
		parse_str($initData, $params);

		if (!is_array($params) || !isset($params['hash'])) {
			return ['ok' => false, 'error' => 'Missing hash.'];
		}

		$hash = (string) $params['hash'];
		unset($params['hash']);

		ksort($params);

		$parts = [];
		foreach ($params as $key => $value) {
			if (is_array($value)) {
				return ['ok' => false, 'error' => 'Invalid initData.'];
			}

			$parts[] = $key . '=' . (string) $value;
		}

		$checkString = implode("\n", $parts);

		$secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
		$calcHash = hash_hmac('sha256', $checkString, $secretKey);

		if (!hash_equals($calcHash, $hash)) {
			return ['ok' => false, 'error' => 'Invalid signature.'];
		}

		$authDate = isset($params['auth_date']) ? (int) $params['auth_date'] : 0;

		if ($authDate > 0 && $maxAgeSeconds > 0) {
			$age = time() - $authDate;

			if ($age < 0 || $age > $maxAgeSeconds) {
				return ['ok' => false, 'error' => 'auth_date expired.'];
			}
		}

		$user = null;

		if (isset($params['user'])) {
			$decodedUser = json_decode((string) $params['user'], true);

			if (is_array($decodedUser)) {
				$user = $decodedUser;
			}
		}

		$telegramId = null;

		if (is_array($user) && isset($user['id'])) {
			$telegramId = (int) $user['id'];
		}

		if ($telegramId === null || $telegramId <= 0) {
			return ['ok' => false, 'error' => 'Missing user.id.'];
		}

		return [
			'ok' => true,
			'telegram_id' => $telegramId,
			'user' => $user ?? [],
		];
	}
}
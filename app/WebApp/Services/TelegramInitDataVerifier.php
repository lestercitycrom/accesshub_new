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
			return ['ok' => false, 'error' => 'Пустые initData.'];
		}

		$params = [];
		parse_str($initData, $params);

		if (!is_array($params) || !isset($params['hash'])) {
			return ['ok' => false, 'error' => 'Отсутствует hash.'];
		}

		$hash = (string) $params['hash'];

		// Basic hash format hardening (64 hex chars)
		if (preg_match('/^[a-f0-9]{64}$/i', $hash) !== 1) {
			return ['ok' => false, 'error' => 'Неверный формат hash.'];
		}

		unset($params['hash']);

		// auth_date MUST exist in secure mode
		if (!isset($params['auth_date'])) {
			return ['ok' => false, 'error' => 'Отсутствует auth_date.'];
		}

		$authDate = (int) $params['auth_date'];

		if ($authDate <= 0) {
			return ['ok' => false, 'error' => 'Неверный auth_date.'];
		}

		if ($maxAgeSeconds > 0) {
			$age = time() - $authDate;

			if ($age < 0 || $age > $maxAgeSeconds) {
				return ['ok' => false, 'error' => 'auth_date истёк.'];
			}
		}

		// Build data-check-string
		ksort($params);

		$parts = [];
		foreach ($params as $key => $value) {
			if (is_array($value)) {
				return ['ok' => false, 'error' => 'Неверные initData.'];
			}

			$parts[] = $key . '=' . (string) $value;
		}

		$checkString = implode("\n", $parts);

		$secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
		$calcHash = hash_hmac('sha256', $checkString, $secretKey);

		if (!hash_equals($calcHash, $hash)) {
			return ['ok' => false, 'error' => 'Неверная подпись.'];
		}

		// user MUST exist
		if (!isset($params['user'])) {
			return ['ok' => false, 'error' => 'Отсутствует user.'];
		}

		$user = json_decode((string) $params['user'], true);

		if (!is_array($user)) {
			return ['ok' => false, 'error' => 'Неверный user.'];
		}

		$telegramId = isset($user['id']) ? (int) $user['id'] : 0;

		if ($telegramId <= 0) {
			return ['ok' => false, 'error' => 'Отсутствует user.id.'];
		}

		return [
			'ok' => true,
			'telegram_id' => $telegramId,
			'user' => $user ?? [],
		];
	}
}

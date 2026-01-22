<?php

declare(strict_types=1);

namespace App\WebApp\Services;

use Illuminate\Support\Str;

final class InitDataValidator
{
	public function validate(string $initData): ?array
	{
		if (empty($initData)) {
			return null;
		}

		// Parse initData string into array
		parse_str($initData, $data);

		if (!isset($data['hash']) || !isset($data['auth_date'])) {
			return null;
		}

		// Check if validation is enabled
		if (!config('accesshub.webapp.validate_init_data', false)) {
			// In dev mode, just return the data without validation
			return $data;
		}

		// Validate signature
		if (!$this->validateSignature($data)) {
			return null;
		}

		// Check auth_date (not too old)
		$authDate = (int) $data['auth_date'];
		$now = time();
		$maxAge = 86400; // 24 hours

		if (($now - $authDate) > $maxAge) {
			return null;
		}

		return $data;
	}

	private function validateSignature(array $data): bool
	{
		$botToken = config('services.telegram.bot_token');

		if (empty($botToken)) {
			return false;
		}

		// Remove hash from data
		$hash = $data['hash'];
		unset($data['hash']);

		// Sort data by key
		ksort($data);

		// Create data string
		$dataString = '';
		foreach ($data as $key => $value) {
			$dataString .= $key . '=' . $value . "\n";
		}
		$dataString = rtrim($dataString);

		// Create secret key
		$secretKey = hash('sha256', $botToken, true);

		// Calculate HMAC
		$calculatedHash = hash_hmac('sha256', $dataString, $secretKey);

		// Compare hashes
		return hash_equals($calculatedHash, $hash);
	}
}
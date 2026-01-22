<?php

declare(strict_types=1);

namespace App\Domain\Settings\Services;

use App\Domain\Settings\Models\Setting;

final class SettingsService
{
	/** @var array<string, mixed> */
	private array $cache = [];

	public function getInt(string $key, int $default): int
	{
		$value = $this->get($key);

		if ($value === null) {
			return $default;
		}

		if (is_int($value)) {
			return $value;
		}

		if (is_string($value) && ctype_digit($value)) {
			return (int) $value;
		}

		return $default;
	}

	/**
	 * @return mixed
	 */
	public function get(string $key)
	{
		if (array_key_exists($key, $this->cache)) {
			return $this->cache[$key];
		}

		$setting = Setting::query()->where('key', $key)->first();

		if ($setting === null) {
			$this->cache[$key] = null;
			return null;
		}

		// We store scalar in JSON as { "v": ... } to keep type stable
		$value = is_array($setting->value) ? ($setting->value['v'] ?? null) : null;

		$this->cache[$key] = $value;

		return $value;
	}

	/**
	 * @param mixed $value
	 */
	public function set(string $key, $value, int $userId): void
	{
		Setting::query()->updateOrCreate(
			['key' => $key],
			[
				'value' => ['v' => $value],
				'updated_by_user_id' => $userId,
			]
		);

		$this->cache[$key] = $value;
	}
}
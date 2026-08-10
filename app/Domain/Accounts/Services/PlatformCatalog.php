<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Services;

final class PlatformCatalog
{
	/** @var list<string> */
	public const OPTIONS = [
		'Steam',
		'Epic Games',
		'PS3',
		'PS4',
		'PS5',
		'Xbox One',
		'Xbox Series X',
		'Xbox One/Series X',
		'Nintendo Switch 1',
		'Nintendo Switch 2',
		'Nintendo Switch 1/2',
		'Origin',
		'Battle.net',
		'GOG',
		'Другое',
	];

	public static function canonicalize(string $platform): ?string
	{
		$key = mb_strtolower(trim($platform));
		$key = preg_replace('/[\s_-]+/u', '', $key) ?? $key;

		return match ($key) {
			'steam' => 'Steam',
			'epic', 'epicgames' => 'Epic Games',
			'ps3' => 'PS3',
			'ps4' => 'PS4',
			'ps5' => 'PS5',
			'xboxone' => 'Xbox One',
			'xboxx', 'xboxseriesx' => 'Xbox Series X',
			'xbox', 'xboxone/xboxx', 'xboxone/seriesx' => 'Xbox One/Series X',
			'nintendoswitch1' => 'Nintendo Switch 1',
			'2', 'nintendoswitch2' => 'Nintendo Switch 2',
			'nintendo', 'nintendoswitch1/2' => 'Nintendo Switch 1/2',
			'origin' => 'Origin',
			'battle.net', 'battlenet' => 'Battle.net',
			'gog' => 'GOG',
			'другое', 'other' => 'Другое',
			default => null,
		};
	}

	/**
	 * @param array<int, string> $platforms
	 * @return list<string>|null Null means at least one unknown value.
	 */
	public static function normalizeList(array $platforms): ?array
	{
		$canonical = [];
		foreach ($platforms as $platform) {
			$value = self::canonicalize((string) $platform);
			if ($value === null) {
				return null;
			}
			$canonical[] = $value;
		}

		$canonical = array_values(array_unique($canonical));

		if (in_array('Nintendo Switch 1', $canonical, true) && in_array('Nintendo Switch 2', $canonical, true)) {
			$canonical = array_values(array_diff($canonical, ['Nintendo Switch 1', 'Nintendo Switch 2']));
			$canonical[] = 'Nintendo Switch 1/2';
		}

		if (in_array('Xbox One', $canonical, true) && in_array('Xbox Series X', $canonical, true)) {
			$canonical = array_values(array_diff($canonical, ['Xbox One', 'Xbox Series X']));
			$canonical[] = 'Xbox One/Series X';
		}

		$order = array_flip(self::OPTIONS);
		usort($canonical, static fn (string $left, string $right): int => $order[$left] <=> $order[$right]);

		return $canonical;
	}
}

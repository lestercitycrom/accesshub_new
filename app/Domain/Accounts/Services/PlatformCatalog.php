<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Services;

final class PlatformCatalog
{
	/** @var list<string> */
	public const OPTIONS = [
		'Steam',
		'Epic Games',
		'Windows',
		'PS3',
		'PS4',
		'PS5',
		'Xbox One',
		'Xbox Series X',
		'Nintendo Switch 1',
		'Nintendo Switch 2',
		'Origin',
		'Battle.net',
		'GOG',
		'Другое',
	];

	public static function canonicalize(string $platform): ?string
	{
		$expanded = self::expand($platform);

		return $expanded !== null && count($expanded) === 1 ? $expanded[0] : null;
	}

	/** @return list<string>|null */
	public static function expand(string $platform): ?array
	{
		$key = mb_strtolower(trim($platform));
		$key = preg_replace('/[\s_-]+/u', '', $key) ?? $key;

		return match ($key) {
			'steam' => ['Steam'],
			'epic', 'epicgames' => ['Epic Games'],
			'windows', 'win', 'pc', 'microsoftwindows' => ['Windows'],
			'ps3' => ['PS3'],
			'ps4' => ['PS4'],
			'ps5' => ['PS5'],
			'ps', 'playstation', 'ps4/ps5' => ['PS4', 'PS5'],
			'xboxone' => ['Xbox One'],
			'xboxx', 'xboxseriesx' => ['Xbox Series X'],
			'xbox', 'xboxone/xboxx', 'xboxone/seriesx' => ['Xbox One', 'Xbox Series X'],
			'nintendoswitch1' => ['Nintendo Switch 1'],
			'2', 'nintendoswitch2' => ['Nintendo Switch 2'],
			'nintendo', 'switch', 'nintendoswitch', 'nintendoswitch1/2' => ['Nintendo Switch 1', 'Nintendo Switch 2'],
			'origin' => ['Origin'],
			'battle.net', 'battlenet' => ['Battle.net'],
			'gog' => ['GOG'],
			'другое', 'other' => ['Другое'],
			default => null,
		};
	}

	/** @return list<string> */
	public static function searchCandidates(string $platform): array
	{
		$expanded = self::expand($platform) ?? [];

		return array_values(array_unique(array_filter([
			$platform,
			...$expanded,
		])));
	}

	/**
	 * @param array<int, string> $platforms
	 * @return list<string>|null Null means at least one unknown value.
	 */
	public static function normalizeList(array $platforms): ?array
	{
		$canonical = [];
		foreach ($platforms as $platform) {
			$values = self::expand((string) $platform);
			if ($values === null) {
				return null;
			}
			array_push($canonical, ...$values);
		}

		$canonical = array_values(array_unique($canonical));

		$order = array_flip(self::OPTIONS);
		usort($canonical, static fn (string $left, string $right): int => $order[$left] <=> $order[$right]);

		return $canonical;
	}
}

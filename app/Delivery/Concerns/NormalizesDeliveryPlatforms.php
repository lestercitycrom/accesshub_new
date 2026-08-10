<?php

declare(strict_types=1);

namespace App\Delivery\Concerns;

/**
 * Shared platform-name normalization for delivery flows.
 *
 * Used by DeliveryOrderService, the WebApp DeliveryOrdersController and the
 * admin DeliveryOrderShow component so that platform aliases resolve the same
 * way everywhere. Keep all alias rules here — do not re-add private copies in
 * the consuming classes.
 */
trait NormalizesDeliveryPlatforms
{
	protected function normalizePlatform(string $platform): string
	{
		$platform = trim($platform);
		$lower = strtolower(str_replace([' ', '_', '-'], '', $platform));

		return match ($lower) {
			'ps', 'playstation' => 'PlayStation',
			'ps4' => 'PS4',
			'ps5' => 'PS5',
			'xb', 'xbox' => 'Xbox',
			'xboxx', 'xboxseriesx' => 'Xbox Series X',
			'xboxone' => 'Xbox One',
			'xboxone/xboxx', 'xboxone/seriesx' => 'Xbox One/Series X',
			'nintendo', 'switch', 'nintendoswitch' => 'Nintendo',
			'nintendo1', 'switch1', 'nintendoswitch1' => 'Nintendo Switch 1',
			'nintendo2', 'switch2', 'nintendoswitch2' => 'Nintendo Switch 2',
			'nintendoswitch1/2' => 'Nintendo Switch 1/2',
			'steam' => 'Steam',
			'epic', 'epicgames' => 'Epic Games',
			default => $platform,
		};
	}

	/**
	 * Account `platform` aliases to search when issuing for a chosen platform.
	 *
	 * @return array<int, string>
	 */
	protected function issuePlatformCandidates(string $platform): array
	{
		$platform = $this->normalizePlatform($platform);

		return match ($platform) {
			'PlayStation' => ['PlayStation', 'PS5', 'PS4'],
			'Xbox' => ['Xbox One/Series X', 'Xbox One/Xbox X', 'Xbox', 'XBox', 'Xbox Series X', 'Xbox X', 'Xbox One'],
			'Xbox One/Series X' => ['Xbox One/Series X', 'Xbox One/Xbox X', 'Xbox', 'XBox', 'Xbox Series X', 'Xbox X', 'Xbox One'],
			'Xbox Series X' => ['Xbox Series X', 'Xbox X', 'Xbox One/Series X', 'Xbox One/Xbox X'],
			'Xbox One' => ['Xbox One', 'Xbox One/Series X', 'Xbox One/Xbox X'],
			'Nintendo' => ['Nintendo Switch 1/2', 'Nintendo', 'Nintendo Switch 2', '2', 'Nintendo Switch 1'],
			'Nintendo Switch 1/2' => ['Nintendo Switch 1/2', 'Nintendo', 'Nintendo Switch 2', '2', 'Nintendo Switch 1'],
			'Nintendo Switch 1' => ['Nintendo Switch 1', 'Nintendo'],
			'Nintendo Switch 2' => ['Nintendo Switch 2', '2', 'Nintendo', 'Nintendo Switch 1/2'],
			'Epic Games' => ['Epic Games', 'EpicGames', 'Epic'],
			default => [$platform],
		};
	}
}

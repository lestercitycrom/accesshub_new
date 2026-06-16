<?php

declare(strict_types=1);

return [
	'link_ttl_hours' => (int) env('DELIVERY_LINK_TTL_HOURS', 72),
	'connection_attempts_limit' => (int) env('DELIVERY_CONNECTION_ATTEMPTS_LIMIT', 3),
	'connection_lock_hours' => (int) env('DELIVERY_CONNECTION_LOCK_HOURS', 24),
	'fake_password_prefix' => env('DELIVERY_FAKE_PASSWORD_PREFIX', 'QR'),
	'polling_interval_seconds' => (int) env('DELIVERY_POLLING_INTERVAL_SECONDS', 8),

	'connection_platforms' => [
		'PlayStation',
		'PS4',
		'PS5',
		'Xbox',
		'Nintendo',
	],

	'direct_delivery_platforms' => [
		'Steam',
		'Epic Games',
		'EpicGames',
		'Epic',
	],
];


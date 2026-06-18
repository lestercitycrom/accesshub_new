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

	// Support / working hours (mirrors the old download-games site).
	// `enforce` = hard-block order creation outside the window (night block).
	// The clock widget is shown regardless; only the block is gated by enforce.
	'working_hours' => [
		'enforce' => filter_var(env('DELIVERY_WORKING_HOURS', false), FILTER_VALIDATE_BOOL),
		'start' => (int) env('DELIVERY_WORKING_HOURS_START', 9),   // 09:00
		'end' => (int) env('DELIVERY_WORKING_HOURS_END', 23),      // 23:00 (24 = midnight)
		'timezone' => env('DELIVERY_WORKING_HOURS_TZ', 'Europe/Kyiv'),
		'label' => env('DELIVERY_WORKING_HOURS_LABEL', 'EET'),
	],

	// Per-platform installation tutorial (legacy pages served on the same domain).
	'tutorial_urls' => [
		'Epic Games' => env('DELIVERY_TUTORIAL_EPIC', 'https://download-games.info/tutorial.php?lang=en&console=epic'),
		'Steam' => env('DELIVERY_TUTORIAL_STEAM', 'https://download-games.info/tutorial.php?lang=en&console=steam'),
	],
];


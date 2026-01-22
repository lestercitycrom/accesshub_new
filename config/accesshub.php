<?php

declare(strict_types=1);

return [
	'issuance' => [
		'max_qty' => 2,

		/*
		 * Minimal rule:
		 * - If operator requests qty >= 2 => apply cooldown_days.
		 * - If account was issued in last 24h => apply cooldown_days.
		 * - Else => no cooldown.
		 */
		'cooldown_days' => 14,
	],

	'stolen' => [
		'default_deadline_days' => 5,
	],
];
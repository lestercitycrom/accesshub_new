<?php

declare(strict_types=1);

return [
	'issuance' => [
		'max_qty' => 2,

		/*
		 * Cooldown modes:
		 * - 'operator_qty': cooldown only when operator requests qty >= 2
		 * - 'rolling_24h': cooldown for account re-issuance within account_cooldown_hours
		 * - 'both': apply both rules
		 */
		'cooldown_mode' => env('ACCESSHUB_COOLDOWN_MODE', 'both'),

		/*
		 * Cooldown periods
		 */
		'operator_cooldown_days' => 14,
		'account_cooldown_hours' => 24,
	],

	'stolen' => [
		'default_deadline_days' => 5,
	],

	'webapp' => [
		'validate_init_data' => env('WEBAPP_VALIDATE_INIT_DATA', false),
		'max_history_items' => 20,
	],
];
<?php

declare(strict_types=1);

return [
	'brand' => [
		'name' => env('ADMIN_KIT_BRAND_NAME', 'Admin'),
		'badge' => env('ADMIN_KIT_BRAND_BADGE', 'AK'),
	],

	'layout' => [
		'container' => env('ADMIN_KIT_CONTAINER', 'max-w-7xl'),
	],

	/**
	 * Navigation items.
	 * Each item: ['label' => 'Accounts', 'route' => 'admin.accounts.index']
	 */
	'nav' => [
		['label' => 'Dashboard', 'route' => 'admin.dashboard'],
		['label' => 'Accounts', 'route' => 'admin.accounts.index'],
		['label' => 'Lookup', 'route' => 'admin.account-lookup'],
		['label' => 'Import', 'route' => 'admin.import.accounts'],
		['label' => 'Telegram Users', 'route' => 'admin.telegram-users.index'],
		['label' => 'Problems', 'route' => 'admin.problems.index'],
		['label' => 'Issuances', 'route' => 'admin.issuances.index'],
		['label' => 'Events', 'route' => 'admin.events.index'],
		['label' => 'Settings', 'route' => 'admin.settings.index'],
	],
];
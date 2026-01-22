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
	 * Each item: ['label' => 'Accounts', 'route' => 'admin.accounts.index', 'icon' => 'database']
	 */
	'nav' => [
		['label' => 'Telegram Users', 'route' => 'admin.telegram-users.index', 'icon' => 'users'],
		['label' => 'Accounts', 'route' => 'admin.accounts.index', 'icon' => 'database'],
		['label' => 'Lookup', 'route' => 'admin.accounts.lookup', 'icon' => 'search'],
		['label' => 'Import', 'route' => 'admin.import.accounts', 'icon' => 'upload'],
		['label' => 'Issuances', 'route' => 'admin.issuances.index', 'icon' => 'list'],
		['label' => 'Events', 'route' => 'admin.events.index', 'icon' => 'list'],
		['label' => 'Problems', 'route' => 'admin.problems.index', 'icon' => 'list'],
		['label' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'settings'],
	],
];
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
		['label' => 'Accounts', 'route' => 'admin.accounts.index', 'icon' => 'users'],
		['label' => 'Telegram Users', 'route' => 'admin.telegram-users.index', 'icon' => 'message-circle'],
		['label' => 'Problems', 'route' => 'admin.problems.index', 'icon' => 'alert-triangle'],
		['label' => 'Import', 'route' => 'admin.import.accounts', 'icon' => 'upload'],
		['label' => 'Logs', 'route' => 'admin.issuances.index', 'icon' => 'file-text'],
		['label' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'settings'],
	],
];
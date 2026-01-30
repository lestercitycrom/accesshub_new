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
		['label' => 'Аккаунты', 'route' => 'admin.accounts.index', 'icon' => 'users'],
		['label' => 'Пользователи Telegram', 'route' => 'admin.telegram-users.index', 'icon' => 'message-circle'],
		['label' => 'Проблемные', 'route' => 'admin.problems.index', 'icon' => 'alert-triangle'],
		['label' => 'Логи', 'route' => 'admin.issuances.index', 'icon' => 'file-text'],
		['label' => 'Сервер', 'route' => 'admin.server.errors', 'icon' => 'alert-triangle'],
		['label' => 'Настройки', 'route' => 'admin.settings.index', 'icon' => 'settings'],
	],
];






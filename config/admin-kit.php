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
	 * Top navigation (max 2 levels). An item may have `children` → renders as a
	 * hover/tap dropdown. Keep it shallow; add items/groups here to extend.
	 */
	'nav' => [
		['label' => 'Аккаунты', 'route' => 'admin.accounts.index', 'icon' => 'users', 'children' => [
			['label' => 'Все аккаунты', 'route' => 'admin.accounts.index', 'icon' => 'users'],
			['label' => 'Проблемные', 'route' => 'admin.problems.index', 'icon' => 'alert-triangle'],
		]],
		['label' => 'Доставки', 'route' => 'admin.delivery-orders.index', 'icon' => 'list', 'children' => [
			['label' => 'Заказы', 'route' => 'admin.delivery-orders.index', 'icon' => 'list'],
			['label' => 'Инструкции', 'route' => 'admin.delivery-instructions.index', 'icon' => 'file-text'],
		]],
		['label' => 'Ссылки', 'route' => 'admin.delivery-links.index', 'icon' => 'link'],
	],

	/**
	 * Items shown under the "Admin User" dropdown on the right.
	 */
	'user_menu' => [
		['label' => 'Настройки', 'route' => 'admin.settings.index', 'icon' => 'settings'],
		['label' => 'Сервер', 'route' => 'admin.server.errors', 'icon' => 'alert-triangle'],
		['label' => 'Логи', 'route' => 'admin.issuances.index', 'icon' => 'file-text'],
		['label' => 'Telegram пользователи', 'route' => 'admin.telegram-users.index', 'icon' => 'message-circle'],
	],
];




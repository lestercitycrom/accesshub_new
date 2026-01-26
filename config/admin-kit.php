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
		['label' => 'РџР°РЅРµР»СЊ', 'route' => 'admin.accounts.index'],
		['label' => 'РђРєРєР°СѓРЅС‚С‹', 'route' => 'admin.accounts.index', 'icon' => 'users'],
		['label' => 'РџРѕР»СЊР·РѕРІР°С‚РµР»Рё Telegram', 'route' => 'admin.telegram-users.index', 'icon' => 'message-circle'],
		['label' => 'РџСЂРѕР±Р»РµРјРЅС‹Рµ', 'route' => 'admin.problems.index', 'icon' => 'alert-triangle'],
		['label' => 'Р›РѕРіРё', 'route' => 'admin.issuances.index', 'icon' => 'file-text'],
		['label' => 'РќР°СЃС‚СЂРѕР№РєРё', 'route' => 'admin.settings.index', 'icon' => 'settings'],
	],
];


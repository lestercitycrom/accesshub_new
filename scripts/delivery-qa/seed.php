<?php

declare(strict_types=1);

// Seeds fixed delivery orders for screenshots and manual QA.
// Run: php artisan tinker scripts/delivery-qa/seed.php
// Tokens: qa-waiting-0001 | qa-qr-0001 | qa-locked-0001

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Enums\DeliveryPasswordType;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Models\DeliveryPlatformInstruction;

DeliveryPlatformInstruction::updateOrCreate(
	['platform' => 'Xbox'],
	[
		'title' => 'Connect your Xbox account',
		'body' => implode("\n", [
			'1. Turn on your Xbox and open Settings.',
			'2. Go to Account and choose Sign in.',
			'3. Select "Sign in with a code" and a code will appear on screen.',
			'4. Enter that code in the field below and press Send.',
			'An operator will then finish the connection for you.',
		]),
		'is_active' => true,
	]
);

$base = [
	'customer_email' => 'customer.longname@example.com',
	'platform' => 'Xbox',
	'token_expires_at' => now()->addHours(72),
	'connection_attempts_limit' => 3,
];

DeliveryOrder::updateOrCreate(['token' => 'qa-waiting-0001'], array_merge($base, [
	'order_number' => 'DE-XBOX-2024-00005',
	'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
]));

DeliveryOrder::updateOrCreate(['token' => 'qa-qr-0001'], array_merge($base, [
	'order_number' => 'DE-XBOX-2024-00007',
	'status' => DeliveryOrderStatus::WAITING_FOR_CONNECTION_CODE,
	'game' => 'EA Sports FC 25',
	'display_login' => 'delivery.account.7741@xbox-delivery-mail.example.com',
	'display_password' => 'Xy7-Kq92-Demo-Pass',
	'display_password_type' => DeliveryPasswordType::FAKE,
	'connection_attempts_used' => 1,
]));

DeliveryOrder::updateOrCreate(['token' => 'qa-locked-0001'], array_merge($base, [
	'order_number' => 'DE-XBOX-2024-00009',
	'status' => DeliveryOrderStatus::LOCKED_24H,
	'game' => 'EA Sports FC 25',
	'display_login' => 'delivery.account.9920@xbox-delivery-mail.example.com',
	'display_password' => 'Zb3-Mn41-Demo-Pass',
	'display_password_type' => DeliveryPasswordType::FAKE,
	'connection_attempts_used' => 3,
	'connection_locked_until' => now()->addHours(24),
]));

echo "Delivery QA seed complete.\n";

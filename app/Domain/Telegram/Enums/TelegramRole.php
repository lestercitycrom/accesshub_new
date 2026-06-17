<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Enums;

enum TelegramRole: string
{
	case OPERATOR = 'operator';
	case DELIVERY_OPERATOR = 'delivery_operator';
	case ADMIN = 'admin';
}

<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Enums;

enum AccountStatus: string
{
	case ACTIVE = 'ACTIVE';
	case TEMP_HOLD = 'TEMP_HOLD';
	case RECOVERY = 'RECOVERY';
	case STOLEN = 'STOLEN';
	case DEAD = 'DEAD';
}
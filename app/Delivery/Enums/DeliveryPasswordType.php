<?php

declare(strict_types=1);

namespace App\Delivery\Enums;

enum DeliveryPasswordType: string
{
	case FAKE = 'fake';
	case REAL = 'real';
}


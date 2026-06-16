<?php

declare(strict_types=1);

namespace App\Delivery\Enums;

enum DeliveryOrderStatus: string
{
	case NEW = 'new';
	case WAITING_FOR_OPERATOR = 'waiting_for_operator';
	case ACCOUNT_ASSIGNED = 'account_assigned';
	case WAITING_FOR_CONNECTION_CODE = 'waiting_for_connection_code';
	case CONNECTION_CODE_SUBMITTED = 'connection_code_submitted';
	case OPERATOR_CONNECTING = 'operator_connecting';
	case CONNECTED = 'connected';
	case CONNECTION_FAILED = 'connection_failed';
	case LOCKED_24H = 'locked_24h';
	case EXPIRED = 'expired';
	case CANCELLED = 'cancelled';
}


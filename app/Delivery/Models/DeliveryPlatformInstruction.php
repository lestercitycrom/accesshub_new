<?php

declare(strict_types=1);

namespace App\Delivery\Models;

use Illuminate\Database\Eloquent\Model;

final class DeliveryPlatformInstruction extends Model
{
	protected $table = 'delivery_platform_instructions';

	protected $fillable = [
		'platform',
		'title',
		'body',
		'is_active',
	];

	protected $casts = [
		'is_active' => 'boolean',
	];
}


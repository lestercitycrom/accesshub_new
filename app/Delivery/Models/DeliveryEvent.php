<?php

declare(strict_types=1);

namespace App\Delivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DeliveryEvent extends Model
{
	public $timestamps = false;

	protected $table = 'delivery_events';

	protected $fillable = [
		'delivery_order_id',
		'type',
		'actor_type',
		'actor_id',
		'payload',
		'created_at',
	];

	protected $casts = [
		'delivery_order_id' => 'integer',
		'payload' => 'array',
		'created_at' => 'datetime',
	];

	public function order(): BelongsTo
	{
		return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
	}
}


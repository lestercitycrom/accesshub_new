<?php

declare(strict_types=1);

namespace App\Delivery\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A pre-generated, single-use delivery link ("stock key").
 *
 * @see database/migrations/2026_06_20_000001_create_delivery_links_table.php
 */
final class DeliveryLink extends Model
{
	use HasFactory;

	protected $table = 'delivery_links';

	protected $fillable = [
		'code',
		'batch',
		'game',
		'note',
		'used_at',
		'delivery_order_id',
	];

	protected $casts = [
		'used_at' => 'datetime',
		'delivery_order_id' => 'integer',
	];

	/** Code length (random url-safe token). Kept short for readable links. */
	public const CODE_LENGTH = 16;

	public function order(): BelongsTo
	{
		return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
	}

	/** @param Builder<DeliveryLink> $query */
	public function scopeUnused(Builder $query): void
	{
		$query->whereNull('used_at');
	}

	/** @param Builder<DeliveryLink> $query */
	public function scopeUsed(Builder $query): void
	{
		$query->whereNotNull('used_at');
	}

	public function isUsed(): bool
	{
		return $this->used_at !== null;
	}

	/**
	 * A url-safe random code. Lowercase alnum only so links are easy to read,
	 * paste and bulk-upload. Uniqueness is enforced by the column's unique index;
	 * callers that bulk-insert must check for collisions (astronomically rare).
	 */
	public static function generateCode(): string
	{
		// 16 chars of [a-z0-9] ≈ 36^16 ≈ 8e24 keyspace — collision-safe at scale.
		return Str::lower(Str::random(self::CODE_LENGTH));
	}
}

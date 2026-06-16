<?php

declare(strict_types=1);

namespace App\Delivery\Models;

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Enums\DeliveryPasswordType;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DeliveryOrder extends Model
{
	use HasFactory;

	protected $table = 'delivery_orders';

	protected $fillable = [
		'token',
		'order_number',
		'customer_email',
		'platform',
		'issue_platform',
		'game',
		'status',
		'token_expires_at',
		'connected_at',
		'cancelled_at',
		'account_id',
		'issuance_id',
		'operator_telegram_id',
		'display_login',
		'display_password',
		'display_password_type',
		'connection_attempts_used',
		'connection_attempts_limit',
		'connection_locked_until',
		'last_connection_code',
		'last_connection_code_submitted_at',
		'meta',
	];

	protected $casts = [
		'status' => DeliveryOrderStatus::class,
		'token_expires_at' => 'datetime',
		'connected_at' => 'datetime',
		'cancelled_at' => 'datetime',
		'account_id' => 'integer',
		'issuance_id' => 'integer',
		'operator_telegram_id' => 'integer',
		'display_password_type' => DeliveryPasswordType::class,
		'connection_attempts_used' => 'integer',
		'connection_attempts_limit' => 'integer',
		'connection_locked_until' => 'datetime',
		'last_connection_code_submitted_at' => 'datetime',
		'meta' => 'array',
	];

	public function account(): BelongsTo
	{
		return $this->belongsTo(Account::class, 'account_id');
	}

	public function issuance(): BelongsTo
	{
		return $this->belongsTo(Issuance::class, 'issuance_id');
	}

	public function operator(): BelongsTo
	{
		return $this->belongsTo(TelegramUser::class, 'operator_telegram_id', 'telegram_id');
	}

	public function events(): HasMany
	{
		return $this->hasMany(DeliveryEvent::class, 'delivery_order_id');
	}

	public function isExpired(): bool
	{
		return $this->token_expires_at !== null && now()->greaterThan($this->token_expires_at);
	}
}


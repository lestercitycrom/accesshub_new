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

final class DeliveryOrderItem extends Model
{
	use HasFactory;

	protected $table = 'delivery_order_items';

	protected $fillable = [
		'delivery_order_id',
		'position',
		'platform',
		'issue_platform',
		'game',
		'status',
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
		'connected_at',
	];

	protected $casts = [
		'position' => 'integer',
		'status' => DeliveryOrderStatus::class,
		'account_id' => 'integer',
		'issuance_id' => 'integer',
		'operator_telegram_id' => 'integer',
		'display_password_type' => DeliveryPasswordType::class,
		'connection_attempts_used' => 'integer',
		'connection_attempts_limit' => 'integer',
		'connection_locked_until' => 'datetime',
		'last_connection_code_submitted_at' => 'datetime',
		'connected_at' => 'datetime',
	];

	public function order(): BelongsTo
	{
		return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
	}

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
}

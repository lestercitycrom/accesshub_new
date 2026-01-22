<?php

declare(strict_types=1);

namespace App\Domain\Issuance\Models;

use App\Domain\Accounts\Models\Account;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Issuance extends Model
{
	use HasFactory;

	protected $table = 'issuances';

	protected $fillable = [
		'telegram_id',
		'account_id',
		'order_id',
		'game',
		'platform',
		'qty',
		'issued_at',
		'cooldown_until',
	];

	protected $casts = [
		'telegram_id' => 'integer',
		'account_id' => 'integer',
		'qty' => 'integer',
		'issued_at' => 'datetime',
		'cooldown_until' => 'datetime',
	];

	public function account(): BelongsTo
	{
		return $this->belongsTo(Account::class, 'account_id');
	}

	public function telegramUser(): BelongsTo
	{
		return $this->belongsTo(TelegramUser::class, 'telegram_id', 'telegram_id');
	}
}
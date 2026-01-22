<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Models;

use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountEvent extends Model
{
	use HasFactory;

	protected $table = 'account_events';

	protected $fillable = [
		'account_id',
		'telegram_id',
		'type',
		'payload',
	];

	protected $casts = [
		'account_id' => 'integer',
		'telegram_id' => 'integer',
		'payload' => 'array',
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
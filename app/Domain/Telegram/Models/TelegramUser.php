<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Models;

use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Issuance\Models\Issuance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TelegramUser extends Model
{
	use HasFactory;

	protected $table = 'telegram_users';

	protected $fillable = [
		'telegram_id',
		'username',
		'first_name',
		'last_name',
		'role',
		'is_active',
	];

	protected $casts = [
		'telegram_id' => 'integer',
		'is_active' => 'boolean',
		'role' => TelegramRole::class,
	];

	public function issuances(): HasMany
	{
		return $this->hasMany(Issuance::class, 'telegram_id', 'telegram_id');
	}
}
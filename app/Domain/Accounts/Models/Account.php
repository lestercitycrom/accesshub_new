<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Models;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Issuance\Models\Issuance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Account extends Model
{
	use HasFactory;

	protected $table = 'accounts';

	protected $fillable = [
		'game',
		'platform',
		'login',
		'password',
		'status',
		'max_uses',
		'available_uses',
		'next_release_at',
		'assigned_to_telegram_id',
		'status_deadline_at',
		'flags',
		'meta',
		'mail_account_login',
		'mail_account_password',
		'comment',
		'two_fa_mail_account_date',
		'recover_code',
	];

	protected $casts = [
		'status' => AccountStatus::class,
		'max_uses' => 'integer',
		'available_uses' => 'integer',
		'next_release_at' => 'datetime',
		'assigned_to_telegram_id' => 'integer',
		'status_deadline_at' => 'datetime',
		'flags' => 'array',
		'meta' => 'array',
		'password' => 'encrypted', // Encrypt/decrypt automatically
		'platform' => 'array', // Array of platforms: ["PS4", "PS5"]
		'mail_account_password' => 'encrypted', // Encrypt/decrypt automatically
		'two_fa_mail_account_date' => 'date',
	];

	public function issuances(): HasMany
	{
		return $this->hasMany(Issuance::class, 'account_id');
	}

	public function events(): HasMany
	{
		return $this->hasMany(AccountEvent::class, 'account_id');
	}
}
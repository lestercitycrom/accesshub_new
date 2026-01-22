<?php

declare(strict_types=1);

namespace App\Domain\Settings\Models;

use Illuminate\Database\Eloquent\Model;

final class Setting extends Model
{
	protected $table = 'settings';

	/** @var array<int, string> */
	protected $fillable = [
		'key',
		'value',
		'updated_by_user_id',
	];

	/** @var array<string, string> */
	protected $casts = [
		'value' => 'array',
	];
}
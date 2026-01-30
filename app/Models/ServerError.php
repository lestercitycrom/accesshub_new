<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ServerError extends Model
{
	protected $table = 'server_errors';

	protected $fillable = [
		'telegram_id',
		'context',
		'path',
		'request_summary',
		'exception_message',
		'exception_class',
		'exception_trace',
	];

	protected $casts = [
		'telegram_id' => 'integer',
		'request_summary' => 'array',
	];

	public static function log(
		string $context,
		\Throwable $e,
		?int $telegramId = null,
		?string $path = null,
		?array $requestSummary = null,
	): self {
		return self::query()->create([
			'telegram_id' => $telegramId,
			'context' => $context,
			'path' => $path,
			'request_summary' => $requestSummary,
			'exception_message' => $e->getMessage(),
			'exception_class' => $e::class,
			'exception_trace' => $e->getTraceAsString(),
		]);
	}
}

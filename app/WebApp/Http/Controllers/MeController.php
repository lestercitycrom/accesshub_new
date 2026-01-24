<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController
{
	public function __invoke(Request $request): JsonResponse
	{
		$telegramId = (int) $request->session()->get('webapp.telegram_id', 0);

		if ($telegramId <= 0) {
			return response()->json(['error' => 'Не инициализировано.'], 403);
		}

		$user = TelegramUser::query()
			->where('telegram_id', $telegramId)
			->first();

		return response()->json([
			'telegram_id' => $telegramId,
			'role' => $user?->role?->value ?? null,
			'is_active' => (bool) ($user?->is_active ?? false),
		]);
	}
}

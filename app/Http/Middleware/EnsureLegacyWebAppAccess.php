<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureLegacyWebAppAccess
{
	public function handle(Request $request, Closure $next): Response
	{
		$telegramId = (int) $request->session()->get('webapp.telegram_id', 0);

		if ($telegramId <= 0) {
			return response()->json(['error' => 'Не инициализировано.'], 403);
		}

		$user = TelegramUser::query()
			->where('telegram_id', $telegramId)
			->first();

		if ($user === null || $user->is_active !== true) {
			return response()->json(['error' => 'Доступ запрещен.'], 403);
		}

		if (!in_array($user->role, [TelegramRole::OPERATOR, TelegramRole::ADMIN], true)) {
			return response()->json(['error' => 'Доступ разрешен только в разделе доставки.'], 403);
		}

		return $next($request);
	}
}

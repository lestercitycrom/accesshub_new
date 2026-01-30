<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ServerError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CaptureServerErrors
{
	private const USER_MESSAGE = 'Произошла внутренняя ошибка сервера. Сообщите администратору номер обращения: #%d.';

	public function handle(Request $request, Closure $next): Response
	{
		try {
			return $next($request);
		} catch (\Throwable $e) {
			$telegramId = (int) $request->session()->get('webapp.telegram_id', 0) ?: null;

			$error = ServerError::log('webapp', $e, $telegramId, $request->path(), [
				'method' => $request->method(),
				'action' => $request->input('action'),
				'keys' => array_keys($request->all()),
			]);

			\Log::error('Webapp error: ' . $e->getMessage(), ['server_error_id' => $error->id]);

			if ($request->expectsJson() || $request->ajax()) {
				return response()->json([
					'ok' => false,
					'error' => sprintf(self::USER_MESSAGE, $error->id),
					'issue_id' => $error->id,
				], 200);
			}

			return response()->json([
				'ok' => false,
				'error' => sprintf(self::USER_MESSAGE, $error->id),
				'issue_id' => $error->id,
			], 200);
		}
	}
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class LogWebAppRequests
{
	public function handle(Request $request, Closure $next): Response
	{
		if (str_starts_with($request->path(), 'webapp/api/issue')) {
			Log::info('LogWebAppRequests: Request intercepted', [
				'method' => $request->method(),
				'path' => $request->path(),
				'url' => $request->fullUrl(),
				'all_input' => $request->all(),
				'headers' => $request->headers->all(),
				'session_id' => $request->session()->getId(),
				'session_telegram_id' => $request->session()->get('webapp.telegram_id', 0),
			]);
		}

		$response = $next($request);

		if (str_starts_with($request->path(), 'webapp/api/issue')) {
			Log::info('LogWebAppRequests: Response sent', [
				'status' => $response->getStatusCode(),
				'content' => $response->getContent(),
			]);
		}

		return $response;
	}
}

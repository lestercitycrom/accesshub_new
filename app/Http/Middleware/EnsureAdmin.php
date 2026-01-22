<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdmin
{
	/**
	 * @param Closure(Request): Response $next
	 */
	public function handle(Request $request, Closure $next): Response
	{
		$user = $request->user();

		if ($user === null) {
			return redirect()->route('login');
		}

		if ((bool) $user->is_admin !== true) {
			abort(403);
		}

		return $next($request);
	}
}
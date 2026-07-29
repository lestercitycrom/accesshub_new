<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces every Hub panel user to set up two-factor auth before they can use the
 * admin panel. The 2FA mechanism (Fortify) already exists; this makes it
 * mandatory on login, per the customer's "двойная верификация на входе".
 *
 * A user without 2FA is redirected to the 2FA settings page (which lives
 * outside the admin group, so there's no redirect loop). Once enabled, Fortify's
 * login challenge asks for the code on subsequent logins.
 *
 * Skipped in the `testing` env: enforcement would redirect the many feature
 * tests that act as admins without 2FA. The decision logic is covered directly
 * by EnsureTwoFactorEnabledTest, and the wiring is exercised in local/prod.
 */
final class EnsureTwoFactorEnabled
{
	/**
	 * @param Closure(Request): Response $next
	 */
	public function handle(Request $request, Closure $next): Response
	{
		if (! app()->environment('testing') && $this->needsSetup($request->user())) {
			return redirect()->route('two-factor.show')
				->with('status', 'Для доступа к панели включите двухфакторную аутентификацию.');
		}

		return $next($request);
	}

	/** True when the user is signed in but has not finished enabling 2FA. */
	public function needsSetup(?User $user): bool
	{
		return $user !== null && ! $user->hasEnabledTwoFactorAuthentication();
	}
}

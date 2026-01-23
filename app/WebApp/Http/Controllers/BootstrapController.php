<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use App\WebApp\Services\TelegramInitDataVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class BootstrapController
{
	public function __construct(
		private readonly TelegramInitDataVerifier $verifier,
	) {}

	public function __invoke(Request $request): Response
	{
		$verify = (bool) config('accesshub.webapp.verify_init_data', false);

		$botToken = (string) config('services.telegram.bot_token');
		$maxAge = (int) config('accesshub.webapp.max_auth_age_seconds', 86400);

		$initData = (string) $request->input('initData', '');
		$devTelegramId = (int) $request->input('telegram_id', 0);

		if ($verify) {
			if ($botToken === '') {
				return response('Server misconfigured.', 500);
			}

			$result = $this->verifier->verify($initData, $botToken, $maxAge);

			if (($result['ok'] ?? false) !== true) {
				return response((string) ($result['error'] ?? 'Forbidden.'), 403);
			}

			$telegramId = (int) $result['telegram_id'];
			$user = is_array($result['user'] ?? null) ? $result['user'] : [];

			TelegramUser::query()->updateOrCreate(
				['telegram_id' => $telegramId],
				[
					'username' => isset($user['username']) ? (string) $user['username'] : null,
					'first_name' => isset($user['first_name']) ? (string) $user['first_name'] : null,
					'last_name' => isset($user['last_name']) ? (string) $user['last_name'] : null,
					'role' => TelegramRole::OPERATOR,
					'is_active' => true,
				]
			);

			// Prevent session fixation
			$request->session()->regenerate();

			$request->session()->put('webapp.telegram_id', $telegramId);

			return response()->noContent();
		}

		// DEV mode: allow manual telegram_id bootstrap
		if ($devTelegramId <= 0) {
			return response('Missing telegram_id (dev bootstrap).', 422);
		}

		TelegramUser::query()->updateOrCreate(
			['telegram_id' => $devTelegramId],
			[
				'username' => (string) $request->input('username', 'dev_user'),
				'first_name' => (string) $request->input('first_name', 'Dev'),
				'last_name' => (string) $request->input('last_name', 'User'),
				'role' => TelegramRole::OPERATOR,
				'is_active' => true,
			]
		);

		$request->session()->regenerate();
		$request->session()->put('webapp.telegram_id', $devTelegramId);

		return response()->noContent();
	}
}
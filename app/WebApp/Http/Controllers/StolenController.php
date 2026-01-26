<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StolenController
{
	public function __invoke(Request $request): JsonResponse
	{
		$telegramId = (int) $request->session()->get('webapp.telegram_id', 0);

		if ($telegramId <= 0) {
			return response()->json(['error' => 'Не инициализировано.'], 403);
		}

		$items = Account::query()
			->where('status', AccountStatus::STOLEN)
			->where('assigned_to_telegram_id', $telegramId)
			->orderBy('status_deadline_at')
			->limit(50)
			->get()
			->map(static fn (Account $account) => [
				'id' => $account->id,
				'login' => $account->login,
				'deadline' => $account->status_deadline_at?->format('Y-m-d H:i') ?? '-',
			])
			->all();

		return response()->json([
			'items' => $items,
		]);
	}
}

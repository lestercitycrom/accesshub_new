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
			->with('assignedOperator')
			->orderBy('status_deadline_at')
			->limit(100)
			->get()
			->map(static function (Account $account) use ($telegramId) {
				$operator = $account->assignedOperator;
				$operatorName = $operator
					? ($operator->username ? '@' . $operator->username : $operator->first_name)
					: '—';

				return [
					'id'            => $account->id,
					'login'         => $account->login,
					'deadline'      => $account->status_deadline_at?->format('Y-m-d H:i') ?? '-',
					'operator'      => $operatorName,
					'is_mine'       => $account->assigned_to_telegram_id === $telegramId,
				];
			})
			->all();

		return response()->json([
			'items' => $items,
		]);
	}
}

<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Issuance\Models\Issuance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HistoryController
{
	public function __invoke(Request $request): JsonResponse
	{
		$telegramId = (int) $request->session()->get('webapp.telegram_id', 0);

		if ($telegramId <= 0) {
			return response()->json(['error' => 'Не инициализировано.'], 403);
		}

		$limit = (int) $request->query('limit', 20);
		$limit = max(1, min(200, $limit));

		$page = (int) $request->query('page', 1);
		$page = max(1, $page);
		$offset = ($page - 1) * $limit;

		$orderId = trim((string) $request->query('order_id', ''));

		$query = Issuance::query()
			->with(['account'])
			->where('telegram_id', $telegramId)
			->orderByDesc('issued_at');

		if ($orderId !== '') {
			$query->where('order_id', $orderId);
		}

		$total = (clone $query)->count();

		$items = $query
			->offset($offset)
			->limit($limit)
			->get()
			->map(static function (Issuance $issuance): array {
			return [
				'order_id' => $issuance->order_id,
				'game' => $issuance->game,
				'platform' => $issuance->platform,
				'qty' => $issuance->qty,
				'issued_at' => $issuance->issued_at?->toDateTimeString(),
				'account_id' => $issuance->account_id,
				'login' => $issuance->account?->login,
				'password' => $issuance->account?->password,
				'comment' => $issuance->account?->comment,
			];
		})->all();

		return response()->json([
			'items' => $items,
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
		]);
	}
}

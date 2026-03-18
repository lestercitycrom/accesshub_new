<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderSearchController
{
    public function __invoke(Request $request): JsonResponse
    {
        $telegramId = (int) $request->session()->get('webapp.telegram_id', 0);

        if ($telegramId <= 0) {
            return response()->json(['error' => 'Не инициализировано.'], 403);
        }

        $orderId = trim((string) $request->query('order_id', ''));

        if ($orderId === '') {
            return response()->json(['error' => 'Укажите номер заказа.'], 422);
        }

        $issuances = Issuance::query()
            ->with(['account'])
            ->where('order_id', $orderId)
            ->orderByDesc('issued_at')
            ->get();

        if ($issuances->isEmpty()) {
            return response()->json(['found' => false, 'items' => []]);
        }

        // Загружаем ники операторов одним запросом
        $operatorIds = $issuances->pluck('telegram_id')->unique()->all();
        $operators = TelegramUser::query()
            ->whereIn('telegram_id', $operatorIds)
            ->get(['telegram_id', 'username', 'first_name'])
            ->keyBy('telegram_id');

        $items = $issuances->map(function (Issuance $issuance) use ($telegramId, $operators): array {
            $op = $operators->get($issuance->telegram_id);
            $operatorName = $op
                ? ($op->username ? '@' . $op->username : $op->first_name)
                : '#' . $issuance->telegram_id;

            return [
                'order_id'      => $issuance->order_id,
                'game'          => $issuance->game,
                'platform'      => $issuance->platform,
                'issued_at'     => $issuance->issued_at?->toDateTimeString(),
                'account_id'    => $issuance->account_id,
                'login'         => $issuance->account?->login,
                'is_mine'       => (int) $issuance->telegram_id === $telegramId,
                'operator'      => $operatorName,
            ];
        })->all();

        return response()->json(['found' => true, 'items' => $items]);
    }
}

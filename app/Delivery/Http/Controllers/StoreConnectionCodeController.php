<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use App\Delivery\Services\DeliveryTelegramNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StoreConnectionCodeController
{
	public function __construct(
		private readonly DeliveryOrderService $orders,
		private readonly DeliveryTelegramNotifier $telegramNotifier,
	) {}

	public function __invoke(Request $request, string $token): JsonResponse
	{
		$order = DeliveryOrder::query()->where('token', $token)->firstOrFail();
		$result = $this->orders->submitConnectionCode($order, (string) $request->input('connection_code', ''));

		if ($result->successful()) {
			// Notify operators after the response is sent (A4) — keeps the public
			// polling endpoint snappy and independent of Telegram latency.
			$notifier = $this->telegramNotifier;
			app()->terminating(static function () use ($notifier, $order): void {
				$notifier->notifyConnectionCodeSubmitted($order);
			});
		}

		return response()->json([
			'ok' => $result->successful(),
			'message' => $result->message(),
			'order' => $this->orders->publicPayload($order),
		], $result->successful() ? 200 : 422);
	}
}

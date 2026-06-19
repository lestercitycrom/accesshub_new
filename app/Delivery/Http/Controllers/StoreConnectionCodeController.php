<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Models\DeliveryOrderItem;
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

		// P.4: the client submits a code for a specific game tab. item=0 (or absent)
		// targets the first game (the order); any other id targets a DeliveryOrderItem.
		$itemId = (int) $request->input('item', 0);
		$holder = $order;
		if ($itemId > 0) {
			$item = DeliveryOrderItem::query()
				->where('id', $itemId)
				->where('delivery_order_id', $order->id)
				->first();
			if ($item === null) {
				return response()->json([
					'ok' => false,
					'message' => 'Game not found in this order.',
					'order' => $this->orders->publicPayload($order),
				], 404);
			}
			$holder = $item;
		}

		$result = $this->orders->submitConnectionCode($holder, (string) $request->input('connection_code', ''));

		if ($result->successful()) {
			$this->telegramNotifier->notifyConnectionCodeSubmitted($holder);
		}

		return response()->json([
			'ok' => $result->successful(),
			'message' => $result->message(),
			'order' => $this->orders->publicPayload($order),
		], $result->successful() ? 200 : 422);
	}
}

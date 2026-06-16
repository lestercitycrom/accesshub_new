<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StoreConnectionCodeController
{
	public function __construct(
		private readonly DeliveryOrderService $orders,
	) {}

	public function __invoke(Request $request, string $token): JsonResponse
	{
		$data = $request->validate([
			'connection_code' => ['required', 'string', 'regex:/^[A-Za-z0-9]{6,8}$/'],
		]);

		$order = DeliveryOrder::query()->where('token', $token)->firstOrFail();
		$result = $this->orders->submitConnectionCode($order, (string) $data['connection_code']);

		return response()->json([
			'ok' => $result->successful(),
			'message' => $result->message(),
			'order' => $this->orders->publicPayload($order),
		], $result->successful() ? 200 : 422);
	}
}


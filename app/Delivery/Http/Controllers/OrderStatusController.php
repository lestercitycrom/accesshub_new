<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use Illuminate\Http\JsonResponse;

final class OrderStatusController
{
	public function __construct(
		private readonly DeliveryOrderService $orders,
	) {}

	public function __invoke(string $token): JsonResponse
	{
		$order = DeliveryOrder::query()->where('token', $token)->firstOrFail();

		return response()->json($this->orders->publicPayload($order));
	}
}


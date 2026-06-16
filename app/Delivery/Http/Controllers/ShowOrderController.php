<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Services\DeliveryOrderService;
use Illuminate\View\View;

final class ShowOrderController
{
	public function __construct(
		private readonly DeliveryOrderService $orders,
	) {}

	public function __invoke(string $token): View
	{
		$order = DeliveryOrder::query()->where('token', $token)->firstOrFail();

		return view('delivery.order', [
			'order' => $order,
			'payload' => $this->orders->publicPayload($order),
		]);
	}
}


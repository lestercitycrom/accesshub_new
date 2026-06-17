<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use App\Delivery\Services\DeliveryOrderService;
use App\Delivery\Services\DeliveryTelegramNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StoreOrderController
{
	public function __construct(
		private readonly DeliveryOrderService $orders,
		private readonly DeliveryTelegramNotifier $telegramNotifier,
	) {}

	public function __invoke(Request $request): RedirectResponse
	{
		$data = $request->validate([
			'order_number' => ['required', 'string', 'min:2', 'max:100'],
			'email' => ['required', 'email:rfc', 'max:255'],
			'platform' => ['required', 'string', 'in:PlayStation,Xbox,Nintendo,Steam,Epic Games'],
		]);

		$order = $this->orders->createFromCustomerInput(
			orderNumber: (string) $data['order_number'],
			customerEmail: (string) $data['email'],
			platform: (string) $data['platform'],
		);

		$this->telegramNotifier->notifyNewOrder($order);

		return redirect()->route('delivery.order.show', ['token' => $order->token]);
	}
}

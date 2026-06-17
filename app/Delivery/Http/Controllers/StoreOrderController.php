<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use App\Delivery\Services\DeliveryOrderService;
use App\Delivery\Services\DeliveryTelegramNotifier;
use App\Delivery\Services\WorkingHours;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StoreOrderController
{
	public function __construct(
		private readonly DeliveryOrderService $orders,
		private readonly DeliveryTelegramNotifier $telegramNotifier,
		private readonly WorkingHours $workingHours,
	) {}

	public function __invoke(Request $request): RedirectResponse
	{
		// Public client site is English-facing; app locale is 'ru' (operators).
		// Force English so validation messages shown to the client are in English.
		app()->setLocale('en');

		// Night block: outside support hours, do not accept new orders.
		if ($this->workingHours->enforced() && !$this->workingHours->isOpen()) {
			return back()->withInput()->with('error', sprintf(
				'Orders are accepted during support hours (%02d:00–%02d:00 %s). Please try again later.',
				$this->workingHours->start(),
				$this->workingHours->end() >= 24 ? 0 : $this->workingHours->end(),
				$this->workingHours->label(),
			));
		}

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

		// Notify operators AFTER the response is sent, so the client is not kept
		// waiting on sequential Telegram API calls (A4). No queue worker needed.
		$notifier = $this->telegramNotifier;
		app()->terminating(static function () use ($notifier, $order): void {
			$notifier->notifyNewOrder($order);
		});

		return redirect()->route('delivery.order.show', ['token' => $order->token]);
	}
}

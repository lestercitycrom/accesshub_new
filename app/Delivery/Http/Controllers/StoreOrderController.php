<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use App\Delivery\Models\DeliveryLink;
use App\Delivery\Services\DeliveryOrderService;
use App\Delivery\Services\DeliveryTelegramNotifier;
use App\Delivery\Services\WorkingHours;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

final class StoreOrderController
{
	public function __construct(
		private readonly DeliveryOrderService $orders,
		private readonly DeliveryTelegramNotifier $telegramNotifier,
		private readonly WorkingHours $workingHours,
	) {}

	public function __invoke(Request $request, ?string $code = null): RedirectResponse
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

		// P.5: anti-spam — max 2 orders per email per hour (IP ceiling stays on
		// the route via throttle middleware). Soft limit; tighten later if needed.
		$rateKey = 'delivery-order:' . sha1(strtolower(trim((string) $data['email'])));
		if (RateLimiter::tooManyAttempts($rateKey, 2)) {
			return back()->withInput()->with('error', 'Too many requests. Please try again later.');
		}
		RateLimiter::hit($rateKey, 3600);

		// Unique "stock key" link: consume the code and the order creation in one
		// atomic step so a double-submit can't create two orders / spend it twice.
		if ($code !== null) {
			$redeem = DB::transaction(function () use ($code, $data): array {
				$link = DeliveryLink::query()->where('code', $code)->lockForUpdate()->first();

				if ($link === null) {
					return ['abort' => 404];
				}

				if ($link->isUsed()) {
					// Already redeemed — bounce the buyer to their existing order.
					return ['order' => $link->order];
				}

				$order = $this->orders->createFromCustomerInput(
					orderNumber: (string) $data['order_number'],
					customerEmail: (string) $data['email'],
					platform: (string) $data['platform'],
					game: $link->game,
				);

				$link->forceFill(['used_at' => now(), 'delivery_order_id' => $order->id])->save();

				return ['order' => $order, 'created' => true];
			});

			if (($redeem['abort'] ?? null) === 404) {
				abort(404);
			}

			$order = $redeem['order'] ?? null;
			if ($order === null) {
				return back()->withInput()->with('error', 'This link has already been used.');
			}

			if ($redeem['created'] ?? false) {
				$notifier = $this->telegramNotifier;
				app()->terminating(static function () use ($notifier, $order): void {
					$notifier->notifyNewOrder($order);
				});
			}

			return redirect()->route('delivery.order.show', ['token' => $order->token]);
		}

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

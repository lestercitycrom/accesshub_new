<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use App\Delivery\Models\DeliveryLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TakeOrderPageController
{
	public function __invoke(?string $code = null): View|RedirectResponse
	{
		// A unique "stock key" link. Validate it before showing the form so dead
		// or already-redeemed codes don't lead to a confusing empty submit.
		if ($code !== null) {
			$link = DeliveryLink::query()->where('code', $code)->first();

			if ($link === null) {
				abort(404);
			}

			if ($link->isUsed()) {
				// Single-use: send the buyer straight to the order they created.
				if ($link->order !== null) {
					return redirect()->route('delivery.order.show', ['token' => $link->order->token]);
				}

				// Used, but the order is gone (deleted) — the link is spent.
				abort(410);
			}
		}

		return view('delivery.take-order', [
			'platforms' => ['PlayStation', 'Xbox', 'Nintendo', 'Steam', 'Epic Games'],
			'code' => $code,
		]);
	}
}

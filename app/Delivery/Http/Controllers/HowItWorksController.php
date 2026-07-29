<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use App\Delivery\Models\DeliveryPlatformInstruction;
use Illuminate\View\View;

final class HowItWorksController
{
	public function __invoke(): View
	{
		// Public info page adapted from the legacy "How to install" guide.
		$order = ['PlayStation', 'Xbox', 'Nintendo', 'Steam', 'Epic Games'];
		$instructions = DeliveryPlatformInstruction::query()
			->where('is_active', true)
			->get()
			->sortBy(fn (DeliveryPlatformInstruction $i): int => array_search($i->platform, $order, true) === false ? 99 : array_search($i->platform, $order, true))
			->values();

		return view('delivery.how-it-works', [
			'instructions' => $instructions,
		]);
	}
}

<?php

declare(strict_types=1);

namespace App\Delivery\Http\Controllers;

use Illuminate\View\View;

final class TakeOrderPageController
{
	public function __invoke(): View
	{
		return view('delivery.take-order', [
			'platforms' => ['PlayStation', 'Xbox', 'Nintendo', 'Steam', 'Epic Games'],
		]);
	}
}


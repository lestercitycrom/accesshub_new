<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;

final class WebAppPageController
{
	public function __invoke(): View
	{
		try {
			return view('webapp.page');
		} catch (\Throwable $e) {
			Log::error('WebAppPageController: Exception rendering view', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			]);
			throw $e;
		}
	}
}

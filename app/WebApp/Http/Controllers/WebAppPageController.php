<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use Illuminate\Contracts\View\View;

final class WebAppPageController
{
	public function __invoke(): View
	{
		return view('webapp.page');
	}
}

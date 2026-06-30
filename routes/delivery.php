<?php

declare(strict_types=1);

use App\Delivery\Http\Controllers\HowItWorksController;
use App\Delivery\Http\Controllers\OrderStatusController;
use App\Delivery\Http\Controllers\ShowOrderController;
use App\Delivery\Http\Controllers\StoreConnectionCodeController;
use App\Delivery\Http\Controllers\StoreOrderController;
use App\Delivery\Http\Controllers\TakeOrderPageController;
use Illuminate\Support\Facades\Route;

Route::withoutMiddleware(['auth', 'admin'])
	->name('delivery.')
	->group(function (): void {
		Route::get('/how-it-works', HowItWorksController::class)->name('how-it-works');

		Route::get('/take-order', TakeOrderPageController::class)->name('take-order');
		Route::post('/take-order', StoreOrderController::class)
			->middleware('throttle:20,1')
			->name('take-order.store');

		// Unique "stock key" links — same form, but the {code} makes each link
		// unique (difmark rejects duplicate keys) and single-use. {code} is
		// constrained to the generated alphabet so it never shadows /take-order.
		Route::get('/take-order/{code}', TakeOrderPageController::class)
			->whereAlphaNumeric('code')
			->name('take-order.coded');
		Route::post('/take-order/{code}', StoreOrderController::class)
			->whereAlphaNumeric('code')
			->middleware('throttle:20,1')
			->name('take-order.coded.store');

		Route::get('/order/{token}', ShowOrderController::class)->name('order.show');
		Route::get('/order/{token}/status', OrderStatusController::class)->name('order.status');
		Route::post('/order/{token}/connection-code', StoreConnectionCodeController::class)
			->middleware('throttle:30,1')
			->name('order.connection-code.store');
	});


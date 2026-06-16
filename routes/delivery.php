<?php

declare(strict_types=1);

use App\Delivery\Http\Controllers\OrderStatusController;
use App\Delivery\Http\Controllers\ShowOrderController;
use App\Delivery\Http\Controllers\StoreConnectionCodeController;
use App\Delivery\Http\Controllers\StoreOrderController;
use App\Delivery\Http\Controllers\TakeOrderPageController;
use Illuminate\Support\Facades\Route;

Route::withoutMiddleware(['auth', 'admin'])
	->name('delivery.')
	->group(function (): void {
		Route::get('/take-order', TakeOrderPageController::class)->name('take-order');
		Route::post('/take-order', StoreOrderController::class)
			->middleware('throttle:20,1')
			->name('take-order.store');

		Route::get('/order/{token}', ShowOrderController::class)->name('order.show');
		Route::get('/order/{token}/status', OrderStatusController::class)->name('order.status');
		Route::post('/order/{token}/connection-code', StoreConnectionCodeController::class)
			->middleware('throttle:30,1')
			->name('order.connection-code.store');
	});


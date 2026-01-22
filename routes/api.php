<?php

use App\Telegram\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', [WebhookController::class, 'handle']);
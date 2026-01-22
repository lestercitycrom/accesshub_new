<?php

use App\WebApp\Http\Controllers\BootstrapController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// WebApp routes
Route::get('/webapp', App\WebApp\Livewire\WebAppPage::class)->name('webapp.page');
Route::post('/webapp/bootstrap', [BootstrapController::class, 'bootstrap'])->name('webapp.bootstrap');
Route::get('/webapp/api/schema', [BootstrapController::class, 'schema'])->name('webapp.schema');

require __DIR__.'/settings.php';

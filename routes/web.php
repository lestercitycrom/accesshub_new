<?php

use App\Admin\Livewire\Dashboard;
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

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
	Route::get('/', App\Livewire\Admin\Dashboard::class)->name('dashboard');

	Route::get('/telegram-users', App\Admin\Livewire\TelegramUsers\TelegramUsersIndex::class)->name('telegram-users.index');
	Route::get('/telegram-users/create', App\Admin\Livewire\TelegramUsers\TelegramUserForm::class)->name('telegram-users.create');
	Route::get('/telegram-users/{telegramUser}/edit', App\Admin\Livewire\TelegramUsers\TelegramUserForm::class)->name('telegram-users.edit');

	Route::get('/accounts', App\Admin\Livewire\Accounts\AccountsIndex::class)->name('accounts.index');
	Route::get('/accounts/create', App\Admin\Livewire\Accounts\AccountForm::class)->name('accounts.create');
	Route::get('/accounts/{account}/edit', App\Admin\Livewire\Accounts\AccountForm::class)->name('accounts.edit');
	Route::get('/accounts/{account}', App\Admin\Livewire\Accounts\AccountShow::class)->name('accounts.show');

	Route::get('/account-lookup', App\Admin\Livewire\Accounts\AccountLookup::class)->name('account-lookup');

	Route::get('/import/accounts', App\Admin\Livewire\Import\ImportAccounts::class)->name('import.accounts');

	Route::get('/issuances', App\Admin\Livewire\Logs\IssuancesIndex::class)->name('issuances.index');
	Route::get('/events', App\Admin\Livewire\Logs\AccountEventsIndex::class)->name('events.index');
});

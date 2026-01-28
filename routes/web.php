<?php

use App\WebApp\Http\Controllers\BootstrapController;
use Illuminate\Support\Facades\Route;

/**
 * Public entrypoint:
 * - Guest: redirect to login
 * - Authenticated: redirect to admin dashboard
 */
Route::get('/', function () {
	if (auth()->check()) {
		return redirect()->route('admin.accounts.index');
	}

	return redirect()->route('login');
})->name('home');

// WebApp routes (no auth required)
Route::withoutMiddleware(['auth', 'admin'])->group(function () {
    Route::get('/webapp', App\WebApp\Http\Controllers\WebAppPageController::class)
        ->middleware('no-cache')
        ->name('webapp');
    Route::post('/webapp/bootstrap', BootstrapController::class)
        ->middleware('throttle:30,1')
        ->name('webapp.bootstrap');
    Route::get('/webapp/api/schema', App\WebApp\Http\Controllers\SchemaController::class)->name('webapp.schema');
    Route::get('/webapp/api/me', App\WebApp\Http\Controllers\MeController::class)->name('webapp.me');
    Route::get('/webapp/api/history', App\WebApp\Http\Controllers\HistoryController::class)->name('webapp.history');
    Route::get('/webapp/api/stolen', App\WebApp\Http\Controllers\StolenController::class)->name('webapp.stolen');
    Route::post('/webapp/api/issue', App\WebApp\Http\Controllers\IssueController::class)
        ->middleware('log-webapp')
        ->name('webapp.issue');
    Route::post('/webapp/api/problem', App\WebApp\Http\Controllers\ProblemController::class)->name('webapp.problem');
    Route::post('/webapp/api/update-password', App\WebApp\Http\Controllers\UpdatePasswordController::class)->name('webapp.update-password');
    Route::post('/webapp/api/recover-stolen', App\WebApp\Http\Controllers\RecoverStolenController::class)->name('webapp.recover-stolen');
    Route::post('/webapp/api/postpone-stolen', App\WebApp\Http\Controllers\PostponeStolenController::class)->name('webapp.postpone-stolen');
});

require __DIR__.'/settings.php';

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
	Route::get('/', fn () => redirect()->route('admin.accounts.index'))->name('index');

	Route::get('/telegram-users', App\Admin\Livewire\TelegramUsers\TelegramUsersIndex::class)->name('telegram-users.index');
	Route::get('/telegram-users/create', App\Admin\Livewire\TelegramUsers\TelegramUserForm::class)->name('telegram-users.create');
	Route::get('/telegram-users/{telegramUser}/edit', App\Admin\Livewire\TelegramUsers\TelegramUserForm::class)->name('telegram-users.edit');

	Route::get('/accounts', App\Admin\Livewire\Accounts\AccountsIndex::class)->name('accounts.index');
	Route::post('/accounts/import', App\Admin\Http\Controllers\Import\AccountsSimpleImportController::class)
		->name('accounts.import');
	Route::get('/accounts/create', App\Admin\Livewire\Accounts\AccountForm::class)->name('accounts.create');
	Route::get('/accounts/{account}/edit', App\Admin\Livewire\Accounts\AccountForm::class)->name('accounts.edit');
	Route::get('/accounts/{account}', App\Admin\Livewire\Accounts\AccountShow::class)->name('accounts.show');

	Route::get('/account-lookup', App\Admin\Livewire\Accounts\AccountLookup::class)->name('account-lookup');

	Route::get('/issuances', App\Admin\Livewire\Logs\IssuancesIndex::class)->name('issuances.index');
	Route::get('/events', App\Admin\Livewire\Logs\AccountEventsIndex::class)->name('events.index');

	Route::get('/problems', App\Admin\Livewire\Problems\ProblemsIndex::class)->name('problems.index');

	Route::get('/settings', App\Admin\Livewire\Settings\SettingsIndex::class)->name('settings.index');

	Route::get('/export/accounts.csv', App\Admin\Http\Controllers\Export\ExportAccountsCsvController::class)->name('export.accounts.csv');
	Route::get('/export/issuances.csv', App\Admin\Http\Controllers\Export\ExportIssuancesCsvController::class)->name('export.issuances.csv');
});

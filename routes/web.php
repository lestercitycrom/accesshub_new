<?php

use App\WebApp\Http\Controllers\BootstrapController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/delivery.php';

/**
 * Public entrypoint:
 * - Guest: redirect to login
 * - Authenticated: redirect to admin dashboard
 */
Route::get('/', function () {
	if (auth()->check()) {
		return redirect()->route('admin.accounts.index');
	}

	// On the live domain nginx serves the legacy site at "/", so this only runs
	// on bare app hosts. Send guests to the login page (not the public take-order
	// form) — operators expect the panel, not the client flow.
	return redirect()->route('login');
})->name('home');

// WebApp routes (no auth required)
Route::withoutMiddleware(['auth', 'admin'])->group(function () {
    Route::get('/webapp', App\WebApp\Http\Controllers\WebAppPageController::class)
        ->middleware('no-cache')
        ->name('webapp');
    Route::get('/webapp/auth/{token}', App\WebApp\Http\Controllers\WebAppTokenAuthController::class)
        ->name('webapp.token-auth');
    Route::post('/webapp/bootstrap', BootstrapController::class)
        ->middleware('throttle:30,1')
        ->name('webapp.bootstrap');
    Route::middleware('capture-server-errors')->group(function () {
        Route::get('/webapp/api/schema', App\WebApp\Http\Controllers\SchemaController::class)->name('webapp.schema');
        Route::get('/webapp/api/me', App\WebApp\Http\Controllers\MeController::class)->name('webapp.me');
        Route::get('/webapp/api/delivery-orders', [App\WebApp\Http\Controllers\DeliveryOrdersController::class, 'index'])->name('webapp.delivery-orders.index');
        Route::get('/webapp/api/delivery-orders/{deliveryOrder}/options', [App\WebApp\Http\Controllers\DeliveryOrdersController::class, 'options'])->name('webapp.delivery-orders.options');
        Route::post('/webapp/api/delivery-orders/{deliveryOrder}/assign', [App\WebApp\Http\Controllers\DeliveryOrdersController::class, 'assign'])->name('webapp.delivery-orders.assign');
        Route::post('/webapp/api/delivery-orders/{deliveryOrder}/add-game', [App\WebApp\Http\Controllers\DeliveryOrdersController::class, 'addGame'])->name('webapp.delivery-orders.add-game');
        Route::post('/webapp/api/delivery-orders/{deliveryOrder}/connecting', [App\WebApp\Http\Controllers\DeliveryOrdersController::class, 'connecting'])->name('webapp.delivery-orders.connecting');
        Route::post('/webapp/api/delivery-orders/{deliveryOrder}/connected', [App\WebApp\Http\Controllers\DeliveryOrdersController::class, 'connected'])->name('webapp.delivery-orders.connected');
        Route::post('/webapp/api/delivery-orders/{deliveryOrder}/failed', [App\WebApp\Http\Controllers\DeliveryOrdersController::class, 'failed'])->name('webapp.delivery-orders.failed');
        Route::post('/webapp/api/delivery-orders/{deliveryOrder}/extra-attempts', [App\WebApp\Http\Controllers\DeliveryOrdersController::class, 'extraAttempts'])->name('webapp.delivery-orders.extra-attempts');
        Route::post('/webapp/api/delivery-orders/{deliveryOrder}/replace', [App\WebApp\Http\Controllers\DeliveryOrdersController::class, 'replace'])->name('webapp.delivery-orders.replace');
        Route::post('/webapp/api/delivery-orders/{deliveryOrder}/cancel', [App\WebApp\Http\Controllers\DeliveryOrdersController::class, 'cancel'])->name('webapp.delivery-orders.cancel');
        Route::middleware('legacy-webapp')->group(function (): void {
            Route::get('/webapp/api/history', App\WebApp\Http\Controllers\HistoryController::class)->name('webapp.history');
            Route::get('/webapp/api/stolen', App\WebApp\Http\Controllers\StolenController::class)->name('webapp.stolen');
            Route::get('/webapp/api/order-search', App\WebApp\Http\Controllers\OrderSearchController::class)->name('webapp.order-search');
            Route::post('/webapp/api/issue', App\WebApp\Http\Controllers\IssueController::class)
                ->middleware('log-webapp')
                ->name('webapp.issue');
            Route::post('/webapp/api/problem', App\WebApp\Http\Controllers\ProblemController::class)->name('webapp.problem');
            Route::post('/webapp/api/update-password', App\WebApp\Http\Controllers\UpdatePasswordController::class)->name('webapp.update-password');
            Route::post('/webapp/api/recover-stolen', App\WebApp\Http\Controllers\RecoverStolenController::class)->name('webapp.recover-stolen');
            Route::post('/webapp/api/postpone-stolen', App\WebApp\Http\Controllers\PostponeStolenController::class)->name('webapp.postpone-stolen');
            Route::post('/webapp/api/replace', App\WebApp\Http\Controllers\ReplaceController::class)->name('webapp.replace');
        });
    });
});

require __DIR__.'/settings.php';

// Admin routes. Access is tiered by role capability:
//   can:hub-view    → any role (admin/manager/operator) — read + fulfillment (orders/issuance/problems)
//   can:hub-supply  → admin + manager — add accounts, create links, edit instructions, export
//   can:hub-manage  → admin only — system config & user management
Route::middleware(['auth', 'can:hub-view'])->prefix('admin')->name('admin.')->group(function (): void {
	Route::get('/', fn () => redirect()->route('admin.accounts.index'))->name('index');

	// ---- Supply tier (admin + manager): add to base / catalog / export ----
	// Registered before the "{account}" catch-all so create/edit resolve first.
	Route::middleware('can:hub-supply')->group(function (): void {
		Route::post('/accounts/import', App\Admin\Http\Controllers\Import\AccountsSimpleImportController::class)->name('accounts.import');
		Route::get('/accounts/create', App\Admin\Livewire\Accounts\AccountForm::class)->name('accounts.create');
		Route::get('/accounts/{account}/edit', App\Admin\Livewire\Accounts\AccountForm::class)->name('accounts.edit');

		Route::get('/delivery-links', App\Admin\Livewire\DeliveryLinks\DeliveryLinksIndex::class)->name('delivery-links.index');
		Route::get('/delivery-instructions', App\Admin\Livewire\DeliveryInstructions\DeliveryInstructionsIndex::class)->name('delivery-instructions.index');

		Route::get('/export/accounts.csv', App\Admin\Http\Controllers\Export\ExportAccountsCsvController::class)->name('export.accounts.csv');
		Route::get('/export/issuances.csv', App\Admin\Http\Controllers\Export\ExportIssuancesCsvController::class)->name('export.issuances.csv');
		Route::get('/export/delivery-links.csv', App\Admin\Http\Controllers\Export\ExportDeliveryLinksCsvController::class)->name('export.delivery-links.csv');
	});

	// ---- View + fulfillment tier (all roles) ----
	// Operators work delivery orders / issuance / problems here.
	Route::get('/accounts', App\Admin\Livewire\Accounts\AccountsIndex::class)->name('accounts.index');
	Route::get('/accounts/{account}', App\Admin\Livewire\Accounts\AccountShow::class)->name('accounts.show');
	Route::get('/account-lookup', App\Admin\Livewire\Accounts\AccountLookup::class)->name('account-lookup');

	Route::get('/delivery-orders', App\Admin\Livewire\DeliveryOrders\DeliveryOrdersIndex::class)->name('delivery-orders.index');
	Route::get('/delivery-orders/{deliveryOrder}', App\Admin\Livewire\DeliveryOrders\DeliveryOrderShow::class)->name('delivery-orders.show');

	Route::get('/issuances', App\Admin\Livewire\Logs\IssuancesIndex::class)->name('issuances.index');
	Route::get('/events', App\Admin\Livewire\Logs\AccountEventsIndex::class)->name('events.index');

	Route::get('/problems', App\Admin\Livewire\Problems\ProblemsIndex::class)->name('problems.index');

	// ---- Manage tier (admin only): system config & user management ----
	Route::middleware('can:hub-manage')->group(function (): void {
		Route::get('/users', App\Admin\Livewire\Users\UsersIndex::class)->name('users.index');

		Route::get('/telegram-users', App\Admin\Livewire\TelegramUsers\TelegramUsersIndex::class)->name('telegram-users.index');
		Route::get('/telegram-users/create', App\Admin\Livewire\TelegramUsers\TelegramUserForm::class)->name('telegram-users.create');
		Route::get('/telegram-users/{telegramUser}/edit', App\Admin\Livewire\TelegramUsers\TelegramUserForm::class)->name('telegram-users.edit');

		Route::get('/settings', App\Admin\Livewire\Settings\SettingsIndex::class)->name('settings.index');

		Route::get('/server', App\Admin\Livewire\Server\ServerErrorsIndex::class)->name('server.errors');
	});
});

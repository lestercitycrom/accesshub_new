<?php

declare(strict_types=1);

namespace Acme\AdminKit;

use Acme\AdminKit\Console\InstallAdminKitCommand;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

final class AdminKitServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/admin-kit.php', 'admin-kit');
	}

	public function boot(): void
	{
		$this->loadViewsFrom(__DIR__ . '/../resources/views', 'admin-kit');

		$this->publishes([
			__DIR__ . '/../config/admin-kit.php' => config_path('admin-kit.php'),
		], 'admin-kit-config');

		// Optional publish: copy components into app so you can use <x-admin.*>
		$this->publishes([
			__DIR__ . '/../resources/views/components/admin' => resource_path('views/components/admin'),
		], 'admin-kit-components');

		// Optional publish: copy layout into app to use layouts.admin
		$this->publishes([
			__DIR__ . '/../resources/views/layouts/admin.blade.php' => resource_path('views/layouts/admin.blade.php'),
		], 'admin-kit-layout');

		// Pagination: default to package view immediately (no publish required).
		Paginator::defaultView('admin-kit::pagination.admin');
		Paginator::defaultSimpleView('admin-kit::pagination.admin');

		if ($this->app->runningInConsole()) {
			$this->commands([
				InstallAdminKitCommand::class,
			]);
		}
	}
}
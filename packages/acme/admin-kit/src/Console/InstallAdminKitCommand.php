<?php

declare(strict_types=1);

namespace Acme\AdminKit\Console;

use Illuminate\Console\Command;

final class InstallAdminKitCommand extends Command
{
	protected $signature = 'admin-kit:install
		{--publish : Publish config/components/layout into the host app}
		{--force : Overwrite existing published files}';

	protected $description = 'Install AdminKit (optionally publish views/components/config into the host app).';

	public function handle(): int
	{
		$this->info('AdminKit is registered. Pagination is active by default (package view).');

		if (! $this->option('publish')) {
			$this->line('To publish config/components/layout, run with: --publish');
			$this->line('Example: php artisan admin-kit:install --publish --force');
			return self::SUCCESS;
		}

		$force = (bool) $this->option('force');

		$this->call('vendor:publish', [
			'--tag' => 'admin-kit-config',
			'--force' => $force,
		]);

		$this->call('vendor:publish', [
			'--tag' => 'admin-kit-components',
			'--force' => $force,
		]);

		$this->call('vendor:publish', [
			'--tag' => 'admin-kit-layout',
			'--force' => $force,
		]);

		$this->info('Published: config + components + layout.');

		$this->line('Now you can use:');
		$this->line('- Layout: resources/views/layouts/admin.blade.php (or keep admin-kit::layouts.admin)');
		$this->line('- Components: <x-admin.card>, <x-admin.button>, <x-admin.input> ...');

		return self::SUCCESS;
	}
}
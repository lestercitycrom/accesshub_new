<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Accounts\Models\Account;
use Illuminate\Console\Command;

final class CheckPlatformFormat extends Command
{
	protected $signature = 'accounts:check-platform-format';
	protected $description = 'Check and fix platform format in accounts table';

	public function handle(): int
	{
		$this->info('Checking platform format in accounts...');

		$total = Account::query()->count();
		$fixed = 0;
		$alreadyJson = 0;
		$stringFormat = 0;

		Account::query()->chunkById(100, function ($accounts) use (&$fixed, &$alreadyJson, &$stringFormat): void {
			foreach ($accounts as $account) {
				$platform = $account->platform;

				// Check if it's already JSON array
				if (is_array($platform)) {
					$alreadyJson++;
					continue;
				}

				// Try to decode JSON
				$decoded = json_decode($platform, true);
				if (is_array($decoded)) {
					$alreadyJson++;
					continue;
				}

				// It's a string, need to convert
				$stringFormat++;
				$account->platform = json_encode([$platform]);
				$account->save();
				$fixed++;
			}
		});

		$this->info("Total accounts: {$total}");
		$this->info("Already JSON format: {$alreadyJson}");
		$this->info("String format found: {$stringFormat}");
		$this->info("Fixed: {$fixed}");

		// Test query
		$this->info("\nTesting query with 'steam' platform...");
		try {
			$count = Account::query()
				->whereJsonContains('platform', 'steam')
				->count();
			$this->info("Found {$count} accounts with 'steam' platform using whereJsonContains");
		} catch (\Exception $e) {
			$this->error("whereJsonContains failed: " . $e->getMessage());
			$this->info("Trying raw SQL...");
			try {
				$count = Account::query()
					->whereRaw('JSON_CONTAINS(platform, ?)', [json_encode('steam')])
					->count();
				$this->info("Found {$count} accounts with 'steam' platform using JSON_CONTAINS");
			} catch (\Exception $e2) {
				$this->error("JSON_CONTAINS also failed: " . $e2->getMessage());
			}
		}

		return 0;
	}
}

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

		// Show sample platform data
		$this->info("\nSample platform data (first 5 accounts):");
		Account::query()->limit(5)->get()->each(function ($account) {
			$platform = $account->platform;
			$type = is_array($platform) ? 'array' : (is_string($platform) ? 'string' : gettype($platform));
			$display = is_array($platform) ? json_encode($platform) : $platform;
			$this->line("  ID {$account->id}: {$display} (type: {$type})");
		});

		// Test different query methods
		$this->info("\nTesting queries with 'steam' platform...");
		
		// Method 1: whereJsonContains
		try {
			$count1 = Account::query()
				->whereJsonContains('platform', 'steam')
				->count();
			$this->info("Method 1 (whereJsonContains): Found {$count1} accounts");
		} catch (\Exception $e) {
			$this->error("Method 1 failed: " . $e->getMessage());
		}

		// Method 2: JSON_CONTAINS with string
		try {
			$count2 = Account::query()
				->whereRaw('JSON_CONTAINS(platform, ?)', [json_encode('steam')])
				->count();
			$this->info("Method 2 (JSON_CONTAINS with string): Found {$count2} accounts");
		} catch (\Exception $e) {
			$this->error("Method 2 failed: " . $e->getMessage());
		}

		// Method 3: JSON_CONTAINS with array
		try {
			$count3 = Account::query()
				->whereRaw('JSON_CONTAINS(platform, ?)', [json_encode(['steam'])])
				->count();
			$this->info("Method 3 (JSON_CONTAINS with array): Found {$count3} accounts");
		} catch (\Exception $e) {
			$this->error("Method 3 failed: " . $e->getMessage());
		}

		// Method 4: JSON_SEARCH
		try {
			$count4 = Account::query()
				->whereRaw('JSON_SEARCH(platform, "one", ?) IS NOT NULL', ['steam'])
				->count();
			$this->info("Method 4 (JSON_SEARCH): Found {$count4} accounts");
		} catch (\Exception $e) {
			$this->error("Method 4 failed: " . $e->getMessage());
		}

		// Method 5: Direct check with LIKE (fallback)
		try {
			$count5 = Account::query()
				->whereRaw('platform LIKE ?', ['%"steam"%'])
				->count();
			$this->info("Method 5 (LIKE fallback): Found {$count5} accounts");
		} catch (\Exception $e) {
			$this->error("Method 5 failed: " . $e->getMessage());
		}

		return 0;
	}
}

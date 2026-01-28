<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Console\Command;

final class TestIssue extends Command
{
	protected $signature = 'accounts:test-issue {game} {platform} {telegram_id?}';
	protected $description = 'Test account issuance with specific game and platform';

	public function handle(IssueService $issueService): int
	{
		$game = $this->argument('game');
		$platform = $this->argument('platform');
		$telegramId = $this->argument('telegram_id');

		if (!$telegramId) {
			// Get first active telegram user
			$user = TelegramUser::query()->where('is_active', true)->first();
			if (!$user) {
				$this->error('No active telegram users found. Create one first.');
				return 1;
			}
			$telegramId = $user->telegram_id;
			$this->info("Using telegram_id: {$telegramId}");
		}

		$this->info("Testing issuance:");
		$this->info("  Game: {$game}");
		$this->info("  Platform: {$platform}");
		$this->info("  Telegram ID: {$telegramId}");

		// Check what accounts exist
		$this->info("\nChecking accounts in database:");
		$totalAccounts = Account::query()
			->where('game', $game)
			->count();
		$this->info("  Total accounts with game='{$game}': {$totalAccounts}");

		$platformAccounts = Account::query()
			->where('game', $game)
			->whereRaw('JSON_SEARCH(platform, "one", ?, NULL, "$[*]") IS NOT NULL', [$platform])
			->count();
		$this->info("  Accounts with game='{$game}' AND platform contains '{$platform}': {$platformAccounts}");

		$activeAccounts = Account::query()
			->where('game', $game)
			->whereRaw('JSON_SEARCH(platform, "one", ?, NULL, "$[*]") IS NOT NULL', [$platform])
			->where('status', 'ACTIVE')
			->count();
		$this->info("  ACTIVE accounts with game='{$game}' AND platform contains '{$platform}': {$activeAccounts}");

		$availableAccounts = Account::query()
			->where('game', $game)
			->whereRaw('JSON_SEARCH(platform, "one", ?, NULL, "$[*]") IS NOT NULL', [$platform])
			->where('status', 'ACTIVE')
			->where(function ($q) {
				$now = now();
				$q->where('available_uses', '>', 0)
					->orWhere(function ($q2) use ($now) {
						$q2->whereNotNull('next_release_at')
							->where('next_release_at', '<=', $now->toDateTimeString());
					});
			})
			->count();
		$this->info("  Available accounts: {$availableAccounts}");

		// Show unique games and platforms
		$this->info("\nAvailable games in database:");
		$games = Account::query()
			->distinct()
			->pluck('game')
			->filter()
			->sort()
			->take(10)
			->each(fn($g) => $this->line("  - {$g}"));

		$this->info("\nAvailable platforms in database:");
		$platforms = Account::query()
			->pluck('platform')
			->filter()
			->flatMap(function ($p) {
				if (is_array($p)) {
					return $p;
				}
				$decoded = json_decode($p, true);
				return is_array($decoded) ? $decoded : [];
			})
			->unique()
			->sort()
			->take(10)
			->each(fn($p) => $this->line("  - {$p}"));

		// Try actual issuance
		$this->info("\nAttempting issuance...");
		$result = $issueService->issue(
			telegramId: (int) $telegramId,
			orderId: 'TEST-' . time(),
			game: $game,
			platform: $platform,
			qty: 1
		);

		if ($result->ok()) {
			$this->info("✓ Success! Issued " . count($result->items) . " account(s)");
			foreach ($result->items as $item) {
				$this->line("  Account ID: {$item['account_id']}, Login: {$item['login']}");
			}
		} else {
			$this->error("✗ Failed: " . ($result->message() ?? 'Unknown error'));
		}

		return $result->ok() ? 0 : 1;
	}
}

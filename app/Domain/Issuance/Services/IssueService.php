<?php

declare(strict_types=1);

namespace App\Domain\Issuance\Services;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\DTO\IssuanceResult;
use App\Domain\Issuance\Models\Issuance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class IssueService
{
	public function issue(
		int $telegramId,
		string $orderId,
		string $game,
		string $platform,
		int $qty
	): IssuanceResult {
		$qty = max(1, $qty);
		$maxQty = (int) config('accesshub.issuance.max_qty', 2);

		if ($qty > $maxQty) {
			return IssuanceResult::fail(sprintf('Qty must be between 1 and %d.', $maxQty));
		}

		// Check operator cooldown for operator_qty and both modes
		if ($this->shouldCheckOperatorCooldown() && $this->hasActiveOperatorCooldown($telegramId, $game, $platform)) {
			return IssuanceResult::fail('Cooldown active. Try later.');
		}

		return DB::transaction(function () use ($telegramId, $orderId, $game, $platform, $qty): IssuanceResult {
			$account = $this->findAvailableAccount($game, $platform, $telegramId);

			if (!$account) {
				return IssuanceResult::fail('No available accounts.');
			}

			$cooldownUntil = $this->calculateCooldownUntil($telegramId, $account->id, $qty);

			// Create issuance
			Issuance::query()->create([
				'telegram_id' => $telegramId,
				'account_id' => $account->id,
				'order_id' => $orderId,
				'game' => $game,
				'platform' => $platform,
				'qty' => $qty,
				'issued_at' => now(),
				'cooldown_until' => $cooldownUntil,
			]);

			AccountEvent::query()->create([
				'account_id' => $account->id,
				'telegram_id' => $telegramId,
				'type' => 'ISSUED',
				'payload' => [
					'order_id' => $orderId,
					'game' => $game,
					'platform' => $platform,
					'qty' => $qty,
					'cooldown_until' => $cooldownUntil?->toISOString(),
				],
			]);

			return IssuanceResult::success($account->id, $account->login, (string) $account->password);
		});
	}

	private function findAvailableAccount(string $game, string $platform, int $telegramId): ?Account
	{
		$query = Account::query()
			->where('game', $game)
			->where('platform', $platform)
			->where('status', AccountStatus::ACTIVE)
			->lockForUpdate();

		// Exclude accounts based on cooldown mode
		$cooldownMode = config('accesshub.issuance.cooldown_mode', 'both');

		if ($cooldownMode === 'rolling_24h' || $cooldownMode === 'both') {
			$accountCooldownHours = (int) config('accesshub.issuance.account_cooldown_hours', 24);
			$recentPeriod = Carbon::now()->subHours($accountCooldownHours);

			$query->whereDoesntHave('issuances', function ($query) use ($recentPeriod): void {
				$query->where('issued_at', '>', $recentPeriod);
			});
		}

		return $query->first();
	}

	private function calculateCooldownUntil(int $telegramId, int $accountId, int $qty): ?Carbon
	{
		$cooldownMode = config('accesshub.issuance.cooldown_mode', 'both');

		$shouldApplyCooldown = match ($cooldownMode) {
			'operator_qty' => $qty >= (int) config('accesshub.issuance.max_qty', 2),
			'rolling_24h' => $this->wasAccountIssuedRecently($accountId),
			'both' => $qty >= (int) config('accesshub.issuance.max_qty', 2) || $this->wasAccountIssuedRecently($accountId),
			default => false,
		};

		if ($shouldApplyCooldown) {
			return Carbon::now()->addDays((int) config('accesshub.issuance.operator_cooldown_days', 14));
		}

		return null;
	}

	private function shouldCheckOperatorCooldown(): bool
	{
		$cooldownMode = config('accesshub.issuance.cooldown_mode', 'both');
		return in_array($cooldownMode, ['operator_qty', 'both'], true);
	}

	private function shouldApplyRollingCooldown(): bool
	{
		$cooldownMode = config('accesshub.issuance.cooldown_mode', 'both');
		return in_array($cooldownMode, ['rolling_24h', 'both'], true);
	}

	private function hasActiveOperatorCooldown(int $telegramId, string $game, string $platform): bool
	{
		return Issuance::query()
			->where('telegram_id', $telegramId)
			->where('game', $game)
			->where('platform', $platform)
			->whereNotNull('cooldown_until')
			->where('cooldown_until', '>', now())
			->exists();
	}

	private function wasAccountIssuedRecently(int $accountId): bool
	{
		$accountCooldownHours = (int) config('accesshub.issuance.account_cooldown_hours', 24);
		$recentPeriod = Carbon::now()->subHours($accountCooldownHours);

		return Issuance::query()
			->where('account_id', $accountId)
			->where('issued_at', '>', $recentPeriod)
			->exists();
	}
}
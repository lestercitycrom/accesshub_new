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
		return DB::transaction(function () use ($telegramId, $orderId, $game, $platform, $qty): IssuanceResult {
			// Find available account - exclude accounts with active cooldowns
			$account = Account::query()
				->where('game', $game)
				->where('platform', $platform)
				->where('status', AccountStatus::ACTIVE)
				->whereDoesntHave('issuances', function ($query): void {
					$query->whereNotNull('cooldown_until')
						->where('cooldown_until', '>', now());
				})
				->lockForUpdate()
				->first();

			if (!$account) {
				return IssuanceResult::error('No available accounts');
			}

			// Check if qty >= max_qty => apply cooldown
			$maxQty = config('accesshub.issuance.max_qty', 2);
			$shouldApplyCooldown = $qty >= $maxQty;

			// Check if account was issued in last 24h
			$last24h = Carbon::now()->subDay();
			$recentIssuance = Issuance::query()
				->where('account_id', $account->id)
				->where('issued_at', '>', $last24h)
				->exists();

			if ($recentIssuance) {
				$shouldApplyCooldown = true;
			}

			$cooldownUntil = null;
			if ($shouldApplyCooldown) {
				$cooldownDays = config('accesshub.issuance.cooldown_days', 14);
				$cooldownUntil = Carbon::now()->addDays($cooldownDays);
			}

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
}
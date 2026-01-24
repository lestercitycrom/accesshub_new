<?php

declare(strict_types=1);

namespace App\Domain\Issuance\Services;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\DTO\IssuanceResult;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class IssueService
{
	public function issue(int $telegramId, string $orderId, string $game, string $platform, int $qty): IssuanceResult
	{
		$qty = max(1, $qty);

		$maxQty = (int) config('accesshub.issuance.max_qty', 2);

		if ($qty > $maxQty) {
			return IssuanceResult::fail('Qty limit exceeded.');
		}

		$orderId = trim($orderId);
		$game = trim($game);
		$platform = trim($platform);

		if ($orderId === '' || $game === '' || $platform === '') {
			return IssuanceResult::fail('Invalid input.');
		}

		$user = TelegramUser::query()->where('telegram_id', $telegramId)->first();

		if ($user === null || $user->is_active !== true) {
			return IssuanceResult::fail('Access denied.');
		}

		if (!in_array($user->role, [TelegramRole::OPERATOR, TelegramRole::ADMIN], true)) {
			return IssuanceResult::fail('Access denied.');
		}

		$cooldownDays = (int) config('accesshub.issuance.cooldown_days', 14);
		$now = CarbonImmutable::now();

		return DB::transaction(function () use ($telegramId, $orderId, $game, $platform, $qty, $cooldownDays, $now): IssuanceResult {
			$alreadyIssuedAccountIds = Issuance::query()
				->where('order_id', $orderId)
				->pluck('account_id')
				->all();

			// Select candidates:
			// - ACTIVE accounts only
			// - either available_uses > 0 OR next_release_at is reached (will normalize to 1)
			$query = Account::query()
				->where('game', $game)
				->where('platform', $platform)
				->where('status', AccountStatus::ACTIVE)
				->when($alreadyIssuedAccountIds !== [], static function ($q) use ($alreadyIssuedAccountIds): void {
					$q->whereNotIn('id', $alreadyIssuedAccountIds);
				})
				->where(static function ($q) use ($now): void {
					$q->where('available_uses', '>', 0)
						->orWhere(static function ($q2) use ($now): void {
							$q2->whereNotNull('next_release_at')
								->where('next_release_at', '<=', $now->toDateTimeString());
						});
				})
				->orderByDesc('available_uses')
				->orderBy('id')
				->lockForUpdate()
				->limit($qty);

			/** @var array<int, Account> $accounts */
			$accounts = $query->get()->all();

			if (count($accounts) < $qty) {
				return IssuanceResult::fail('Not enough accounts available.');
			}

			$items = [];

			foreach ($accounts as $account) {
				$this->normalizeAvailability($account, $now);

				if ($account->available_uses <= 0) {
					// Should not happen, but keep safe
					return IssuanceResult::fail('Account is not available.');
				}

				$account->available_uses -= 1;

				if ($account->available_uses === 0) {
					$account->next_release_at = $now->addDays($cooldownDays);
				}

				$account->save();

				$issuance = Issuance::query()->create([
					'order_id' => $orderId,
					'telegram_id' => $telegramId,
					'account_id' => $account->id,
					'game' => $game,
					'platform' => $platform,
					'issued_at' => $now,
					'payload' => [
						'qty' => $qty,
					],
				]);

				AccountEvent::query()->create([
					'account_id' => $account->id,
					'telegram_id' => $telegramId,
					'type' => 'ISSUED',
					'payload' => [
						'order_id' => $orderId,
						'issuance_id' => $issuance->id,
						'game' => $game,
						'platform' => $platform,
					],
				]);

				$items[] = [
					'account_id' => (int) $account->id,
					'login' => (string) $account->login,
					'password' => (string) $account->password,
				];
			}

			return IssuanceResult::success($items);
		});
	}

	private function normalizeAvailability(Account $account, CarbonImmutable $now): void
	{
		if ($account->next_release_at === null) {
			return;
		}

		$next = CarbonImmutable::parse($account->next_release_at);

		if ($now->greaterThanOrEqualTo($next)) {
			// TЗ v3: after cooldown, allow exactly 1 issuance
			$account->available_uses = 1;
			$account->next_release_at = null;
		}
	}
}

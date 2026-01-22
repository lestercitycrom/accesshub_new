<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Services;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class AccountStatusService
{
	public function setStatus(int $accountId, AccountStatus $status, ?int $telegramId, array $payload = []): void
	{
		DB::transaction(function () use ($accountId, $status, $telegramId, $payload): void {
			$account = Account::query()
				->lockForUpdate()
				->findOrFail($accountId);

			$account->status = $status;
			$account->save();

			AccountEvent::query()->create([
				'account_id' => $account->id,
				'telegram_id' => $telegramId,
				'type' => 'SET_STATUS',
				'payload' => array_merge([
					'status' => $status->value,
				], $payload),
			]);
		});
	}

	public function markProblem(int $accountId, int $telegramId, string $reason, array $payload = []): void
	{
		DB::transaction(function () use ($accountId, $telegramId, $reason, $payload): void {
			$account = Account::query()
				->lockForUpdate()
				->findOrFail($accountId);

			$normalizedReason = mb_strtolower(trim($reason));
			$now = Carbon::now();

			$flags = is_array($account->flags) ? $account->flags : [];

			// Minimal mapping (can be expanded later)
			if (in_array($normalizedReason, ['wrong_password', 'password', 'bad_pass'], true)) {
				$account->status = AccountStatus::RECOVERY;
				$flags['PASSWORD_UPDATE_REQUIRED'] = true;
			} elseif (in_array($normalizedReason, ['stolen', 'hijacked'], true)) {
				$account->status = AccountStatus::STOLEN;
				$account->assigned_to_telegram_id = $telegramId;

				$deadlineDays = (int) config('accesshub.stolen.default_deadline_days', 5);
				$account->status_deadline_at = $now->copy()->addDays($deadlineDays);

				$flags['ACTION_REQUIRED'] = true;
			} elseif (in_array($normalizedReason, ['dead'], true)) {
				$account->status = AccountStatus::DEAD;
			} else {
				$account->status = AccountStatus::TEMP_HOLD;
			}

			$account->flags = $flags;
			$account->save();

			AccountEvent::query()->create([
				'account_id' => $account->id,
				'telegram_id' => $telegramId,
				'type' => 'MARK_PROBLEM',
				'payload' => array_merge([
					'reason' => $reason,
					'normalized_reason' => $normalizedReason,
					'new_status' => $account->status->value,
				], $payload),
			]);
		});
	}

	public function updatePassword(int $accountId, string $newPassword, ?int $telegramId, array $payload = []): void
	{
		DB::transaction(function () use ($accountId, $newPassword, $telegramId, $payload): void {
			$account = Account::query()
				->lockForUpdate()
				->findOrFail($accountId);

			$account->password = $newPassword;
			$account->status = AccountStatus::ACTIVE;

			$flags = is_array($account->flags) ? $account->flags : [];
			unset($flags['PASSWORD_UPDATE_REQUIRED']);
			$account->flags = $flags;

			$account->save();

			AccountEvent::query()->create([
				'account_id' => $account->id,
				'telegram_id' => $telegramId,
				'type' => 'PASSWORD_UPDATED',
				'payload' => array_merge([
					'new_status' => AccountStatus::ACTIVE->value,
				], $payload),
			]);
		});
	}

	public function recoverStolen(int $accountId, string $newPassword, ?int $telegramId, array $payload = []): void
	{
		DB::transaction(function () use ($accountId, $newPassword, $telegramId, $payload): void {
			$account = Account::query()
				->lockForUpdate()
				->findOrFail($accountId);

			$account->password = $newPassword;
			$account->status = AccountStatus::ACTIVE;
			$account->assigned_to_telegram_id = null;
			$account->status_deadline_at = null;

			$flags = is_array($account->flags) ? $account->flags : [];
			unset($flags['ACTION_REQUIRED']);
			$account->flags = $flags;

			$account->save();

			AccountEvent::query()->create([
				'account_id' => $account->id,
				'telegram_id' => $telegramId,
				'type' => 'STOLEN_RECOVERED',
				'payload' => array_merge([
					'new_status' => AccountStatus::ACTIVE->value,
				], $payload),
			]);
		});
	}
}
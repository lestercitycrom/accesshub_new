<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Services;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Settings\Services\SettingsService;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class AccountStatusService
{
	public function __construct(
		private readonly SettingsService $settings,
	) {}

	public function setStatus(int $accountId, AccountStatus $status, ?int $telegramId, array $payload = []): void
	{
		DB::transaction(function () use ($accountId, $status, $telegramId, $payload): void {
			$account = Account::query()
				->lockForUpdate()
				->findOrFail($accountId);

			$account->status = $status;

			if ($status === AccountStatus::STOLEN) {
				$assignedId = isset($payload['assigned_to_telegram_id'])
					? (int) $payload['assigned_to_telegram_id']
					: null;
				if ($assignedId > 0) {
					$account->assigned_to_telegram_id = $assignedId;
					$deadlineDays = $this->settings->getInt('stolen_default_deadline_days', (int) config('accesshub.stolen.default_deadline_days', 5));
					$account->status_deadline_at = Carbon::now()->addDays($deadlineDays);
					$flags = is_array($account->flags) ? $account->flags : [];
					$flags['ACTION_REQUIRED'] = true;
					$account->flags = $flags;
				}
			} else {
				$account->assigned_to_telegram_id = null;
				$account->status_deadline_at = null;
				$flags = is_array($account->flags) ? $account->flags : [];
				unset($flags['ACTION_REQUIRED']);
				$account->flags = $flags;
			}

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
			$isAdmin = $this->isAdminByTelegramId($telegramId);

			// Minimal mapping (can be expanded later)
			if (in_array($normalizedReason, ['wrong_password', 'password', 'bad_pass'], true)) {
				$account->status = AccountStatus::TEMP_HOLD;
				$flags['PASSWORD_UPDATE_REQUIRED'] = true;
			} elseif (in_array($normalizedReason, ['no_email', 'no_mail', 'email', 'mail', 'no_access_email'], true)) {
				$account->status = AccountStatus::RECOVERY;
			} elseif (in_array($normalizedReason, ['blocked', 'not_allowed', 'banned', 'login_blocked'], true)) {
				$account->status = AccountStatus::TEMP_HOLD;
			} elseif (in_array($normalizedReason, ['stolen', 'hijacked'], true)) {
				$account->status = AccountStatus::STOLEN;
				$account->assigned_to_telegram_id = $telegramId;

				$deadlineDays = $this->settings->getInt('stolen_default_deadline_days', (int) config('accesshub.stolen.default_deadline_days', 5));
				$account->status_deadline_at = $now->copy()->addDays($deadlineDays);

				$flags['ACTION_REQUIRED'] = true;
			} elseif (in_array($normalizedReason, ['dead'], true)) {
				if ($isAdmin) {
					$account->status = AccountStatus::DEAD;
				} else {
					$account->status = AccountStatus::TEMP_HOLD;
					$payload['requested_status'] = AccountStatus::DEAD->value;
					$payload['denied'] = true;
				}
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

	public function releaseToPool(int $accountId, ?int $telegramId, array $payload = []): void
	{
		DB::transaction(function () use ($accountId, $telegramId, $payload): void {
			$account = Account::query()
				->lockForUpdate()
				->findOrFail($accountId);

			$account->status = AccountStatus::ACTIVE;
			$account->assigned_to_telegram_id = null;
			$account->status_deadline_at = null;

			$flags = is_array($account->flags) ? $account->flags : [];
			unset($flags['ACTION_REQUIRED']);
			unset($flags['PASSWORD_UPDATE_REQUIRED']);
			$account->flags = $flags;

			$account->save();

			AccountEvent::query()->create([
				'account_id' => $account->id,
				'telegram_id' => $telegramId,
				'type' => 'RELEASE_TO_POOL',
				'payload' => array_merge([
					'new_status' => AccountStatus::ACTIVE->value,
				], $payload),
			]);
		});
	}

	public function adminUpdatePassword(int $accountId, string $newPassword, ?int $telegramId, array $payload = []): void
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
			unset($flags['PASSWORD_UPDATE_REQUIRED']);
			unset($flags['ACTION_REQUIRED']);
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

	public function extendDeadline(int $accountId, int $days, ?int $telegramId, array $payload = []): bool
	{
		if ($days <= 0) {
			return false;
		}

		return DB::transaction(function () use ($accountId, $days, $telegramId, $payload): bool {
			$account = Account::query()
				->lockForUpdate()
				->findOrFail($accountId);

			if ($account->status !== AccountStatus::STOLEN) {
				return false;
			}

			$currentDeadline = $account->status_deadline_at ?? now();
			$account->status_deadline_at = $currentDeadline->addDays($days);
			$account->save();

			AccountEvent::query()->create([
				'account_id' => $account->id,
				'telegram_id' => $telegramId,
				'type' => 'EXTEND_DEADLINE',
				'payload' => array_merge([
					'days_added' => $days,
					'new_deadline' => $account->status_deadline_at->toDateTimeString(),
				], $payload),
			]);

			return true;
		});
	}

	private function isAdminByTelegramId(int $telegramId): bool
	{
		$user = TelegramUser::query()
			->where('telegram_id', $telegramId)
			->first();

		return $user?->role === TelegramRole::ADMIN;
	}
}

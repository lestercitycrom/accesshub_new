<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Services;

use App\Domain\Accounts\Models\Account;
use Illuminate\Support\Facades\DB;

final class AccountDeletionService
{
	public function deleteOne(int $accountId): int
	{
		if ($accountId <= 0) {
			return 0;
		}

		return DB::transaction(function () use ($accountId): int {
			DB::table('issuances')->where('account_id', $accountId)->delete();

			return Account::query()->whereKey($accountId)->delete();
		});
	}

	public function deleteAll(): int
	{
		return DB::transaction(function (): int {
			DB::table('issuances')->delete();

			return Account::query()->delete();
		});
	}
}

<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Models\Issuance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AvailableAccountsController
{
	public function __invoke(Request $request): JsonResponse
	{
		$platform = trim((string) $request->query('platform', ''));
		$game = trim((string) $request->query('game', ''));
		$orderId = trim((string) $request->query('order_id', ''));

		if ($platform === '' || $game === '') {
			return response()->json(['items' => []]);
		}

		$alreadyIssuedAccountIds = $orderId === ''
			? []
			: Issuance::query()->where('order_id', $orderId)->pluck('account_id')->all();

		$accounts = Account::query()
			->where('game', $game)
			->where('status', AccountStatus::ACTIVE)
			->whereJsonContains('platform', $platform)
			->when($alreadyIssuedAccountIds !== [], static function ($query) use ($alreadyIssuedAccountIds): void {
				$query->whereNotIn('id', $alreadyIssuedAccountIds);
			})
			->where(static function ($query): void {
				$query->where('available_uses', '>', 0)
					->orWhere(static function ($nested): void {
						$nested->whereNotNull('next_release_at')
							->where('next_release_at', '<=', now());
					});
			})
			->orderByDesc('available_uses')
			->orderBy('id')
			->limit(200)
			->get(['id', 'login', 'available_uses', 'next_release_at', 'source_label'])
			->map(static fn (Account $account): array => [
				'id' => (int) $account->id,
				'login' => (string) $account->login,
				'available_uses' => $account->available_uses > 0 ? (int) $account->available_uses : 1,
				'source_label' => $account->source_label,
			])
			->all();

		return response()->json(['items' => $accounts]);
	}
}

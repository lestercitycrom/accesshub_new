<?php

declare(strict_types=1);

namespace App\WebApp\Http\Controllers;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Settings\Services\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReplaceController
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $telegramId = (int) $request->session()->get('webapp.telegram_id', 0);

        if ($telegramId <= 0) {
            return response()->json(['error' => 'Не инициализировано.'], 403);
        }

        $issuanceId = (int) $request->input('issuance_id', 0);
        $reason     = trim((string) $request->input('reason', ''));

        if ($issuanceId <= 0 || $reason === '') {
            return response()->json(['error' => 'Укажите выдачу и причину замены.'], 422);
        }

        return DB::transaction(function () use ($telegramId, $issuanceId, $reason): JsonResponse {
            $original = Issuance::query()
                ->with('account')
                ->lockForUpdate()
                ->find($issuanceId);

            if ($original === null) {
                return response()->json(['error' => 'Выдача не найдена.'], 404);
            }

            if (!empty($original->payload['replaced'])) {
                return response()->json(['error' => 'Эта выдача уже была заменена.'], 422);
            }

            $now     = CarbonImmutable::now();
            $isBroken = $reason === 'dead';

            // Find replacement account (same game+platform, different account, available)
            $replacement = Account::query()
                ->where('game', $original->game)
                ->where('status', AccountStatus::ACTIVE)
                ->whereJsonContains('platform', $original->platform)
                ->where('id', '!=', $original->account_id)
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
                ->first();

            if ($replacement === null) {
                return response()->json(['error' => 'Нет доступных аккаунтов для замены.'], 422);
            }

            // Normalize cooldown expiry on replacement account
            if ($replacement->next_release_at !== null) {
                $next = CarbonImmutable::parse($replacement->next_release_at);
                if ($now->greaterThanOrEqualTo($next)) {
                    $replacement->available_uses = 1;
                    $replacement->next_release_at = null;
                }
            }

            // Decrement replacement account
            $replacement->available_uses -= 1;
            $cooldownDays = $this->settings->getInt(
                'cooldown_days',
                (int) config('accesshub.issuance.cooldown_days', 14)
            );
            if ($replacement->available_uses === 0) {
                $replacement->next_release_at = $now->addDays($cooldownDays);
            }
            $replacement->save();

            // Restore +1 use to original account unless reason is "dead"
            $originalAccount = $original->account;
            if (!$isBroken && $originalAccount !== null) {
                $originalAccount->available_uses = min(
                    $originalAccount->available_uses + 1,
                    $originalAccount->max_uses
                );
                if ($originalAccount->available_uses > 0 && $originalAccount->next_release_at !== null) {
                    $originalAccount->next_release_at = null;
                }
                $originalAccount->save();
            }

            // Mark original issuance as replaced
            $original->payload = array_merge($original->payload ?? [], [
                'replaced'                => true,
                'replaced_by_telegram_id' => $telegramId,
                'replaced_at'             => $now->toDateTimeString(),
                'replacement_reason'      => $reason,
            ]);
            $original->save();

            // Create replacement issuance
            $newIssuance = Issuance::query()->create([
                'order_id'   => $original->order_id,
                'telegram_id' => $telegramId,
                'account_id' => $replacement->id,
                'game'       => $original->game,
                'platform'   => $original->platform,
                'qty'        => 1,
                'issued_at'  => $now,
                'payload'    => [
                    'is_replacement'       => true,
                    'original_issuance_id' => $original->id,
                    'replacement_reason'   => $reason,
                ],
            ]);

            AccountEvent::query()->create([
                'account_id'  => $replacement->id,
                'telegram_id' => $telegramId,
                'type'        => 'ISSUED',
                'payload'     => [
                    'order_id'             => $original->order_id,
                    'issuance_id'          => $newIssuance->id,
                    'is_replacement'       => true,
                    'replacement_reason'   => $reason,
                    'game'                 => $original->game,
                    'platform'             => $original->platform,
                ],
            ]);

            return response()->json([
                'ok'         => true,
                'message'    => 'Замена выполнена.',
                'account_id' => $replacement->id,
                'login'      => (string) $replacement->login,
                'password'   => (string) $replacement->password,
            ]);
        });
    }
}

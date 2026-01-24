<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Telegram\Models\TelegramUser;
use App\Telegram\Services\TelegramClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class StolenRemindersCommand extends Command
{
	protected $signature = 'accesshub:stolen-remind {--dry-run}';

	protected $description = 'Send reminders for STOLEN accounts assigned to operators';

	public function handle(TelegramClient $telegramClient): int
	{
		$now = CarbonImmutable::now();
		$dryRun = (bool) $this->option('dry-run');

		$accounts = Account::query()
			->where('status', AccountStatus::STOLEN)
			->whereNotNull('assigned_to_telegram_id')
			->where(static function ($query) use ($now): void {
				$query->whereNull('status_deadline_at')
					->orWhere('status_deadline_at', '<=', $now->toDateTimeString());
			})
			->get();

		$sent = 0;

		foreach ($accounts as $account) {
			$chatId = (string) $account->assigned_to_telegram_id;
			$deadline = $account->status_deadline_at?->toDateTimeString() ?? 'не задан';

			$message =
				"Напоминание: аккаунт #{$account->id} помечен как STOLEN.\n" .
				"Логин: <code>{$account->login}</code>\n" .
				"Дедлайн: {$deadline}";

			if ($dryRun) {
				$this->line("[dry-run] {$chatId} -> {$account->id}");
				continue;
			}

			$ok = $telegramClient->sendMessage($chatId, $message);

			$eventTelegramId = TelegramUser::query()
				->where('telegram_id', (int) $account->assigned_to_telegram_id)
				->exists()
				? (int) $account->assigned_to_telegram_id
				: null;

			AccountEvent::query()->create([
				'account_id' => $account->id,
				'telegram_id' => $eventTelegramId,
				'type' => 'STOLEN_REMINDER_SENT',
				'payload' => [
					'sent' => $ok,
					'deadline' => $deadline,
				],
			]);

			if ($ok) {
				$sent += 1;
			}
		}

		$this->info('Reminders sent: ' . $sent);

		return Command::SUCCESS;
	}
}

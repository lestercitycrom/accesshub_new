<?php

declare(strict_types=1);

namespace App\WebApp\Livewire;

use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Accounts\Services\AccountStatusService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.webapp')]
final class WebAppPage extends Component
{
	public string $tab = 'issue';

	public string $orderId = '';
	public string $platform = 'steam';
	public string $game = 'cs2';
	public int $qty = 1;

	public ?string $resultText = null;

	/** @var Collection<int, Issuance> */
	public Collection $history;
	/** @var Collection<int, \App\Domain\Accounts\Models\Account> */
	public Collection $stolenAccounts;

	// Password update form (shown per row)
	public ?int $passwordAccountId = null;
	public string $newPassword = '';
	public string $passwordMode = 'update';

	public function mount(): void
	{
		$this->history = collect();
		$this->stolenAccounts = collect();

		$telegramId = $this->telegramId();

		if ($telegramId > 0) {
			$this->loadHistory($telegramId);
			$this->loadStolen($telegramId);
		}
	}

	public function setTab(string $tab): void
	{
		$allowed = ['issue', 'history'];

		if (!in_array($tab, $allowed, true)) {
			return;
		}

		$this->tab = $tab;

		if ($tab === 'history') {
			$telegramId = $this->telegramId();

			if ($telegramId > 0) {
				$this->loadHistory($telegramId);
				$this->loadStolen($telegramId);
			}
		}
	}

	public function issue(IssueService $service): void
	{
		$telegramId = $this->telegramId();

		if ($telegramId <= 0) {
			$this->resultText = 'WebApp не инициализирован. Откройте внутри Telegram и попробуйте снова.';
			return;
		}

		$orderId = trim($this->orderId);
		$platform = trim($this->platform);
		$game = trim($this->game);
		$qty = max(1, (int) $this->qty);

		if ($orderId === '' || $platform === '' || $game === '') {
			$this->resultText = 'Заполните все поля.';
			return;
		}

		$result = $service->issue($telegramId, $orderId, $game, $platform, $qty);

		if ($result->ok() !== true) {
			$this->resultText = (string) ($result->message() ?? 'Ошибка.');
			$this->loadHistory($telegramId);
			return;
		}

		$this->resultText = $this->formatIssuanceItems($result->items);


		$this->loadHistory($telegramId);
		$this->loadStolen($telegramId);
		$this->tab = 'history';
	}

	public function markProblem(int $accountId, string $reason, AccountStatusService $service, IssueService $issueService): void
	{
		$telegramId = $this->telegramId();

		if ($telegramId <= 0) {
			$this->resultText = 'WebApp не инициализирован.';
			return;
		}

		$service->markProblem($accountId, $telegramId, $reason, [
			'source' => 'webapp',
		]);

		$issuance = Issuance::query()
			->where('account_id', $accountId)
			->where('telegram_id', $telegramId)
			->orderByDesc('issued_at')
			->first();

		if ($issuance === null) {
			$this->resultText = sprintf('Проблема сохранена: %s (аккаунт #%d).', $reason, $accountId);
			$this->loadHistory($telegramId);
			$this->loadStolen($telegramId);
			$this->tab = 'history';
			return;
		}

		$replacement = $issueService->issue(
			telegramId: $telegramId,
			orderId: (string) $issuance->order_id,
			game: (string) $issuance->game,
			platform: (string) $issuance->platform,
			qty: 1,
		);

		if ($replacement->ok() !== true) {
			$this->resultText = sprintf(
				'Проблема сохранена: %s (аккаунт #%d). Замена не выдана: %s',
				$reason,
				$accountId,
				(string) ($replacement->message() ?? 'Ошибка.')
			);
		} else {
			$this->resultText = "Проблема сохранена. Выдана замена:\n\n" . $this->formatIssuanceItems($replacement->items);
		}

		$this->loadHistory($telegramId);
		$this->loadStolen($telegramId);
		$this->tab = 'history';
	}

	public function openPasswordForm(int $accountId, string $mode = 'update'): void
	{
		$this->passwordAccountId = $accountId;
		$this->newPassword = '';
		$this->passwordMode = $mode;
		$this->resultText = null;
	}

	public function cancelPasswordForm(): void
	{
		$this->passwordAccountId = null;
		$this->newPassword = '';
		$this->passwordMode = 'update';
	}

	public function submitPassword(AccountStatusService $service): void
	{
		$telegramId = $this->telegramId();

		if ($telegramId <= 0) {
			$this->resultText = 'WebApp не инициализирован.';
			return;
		}

		if ($this->passwordAccountId === null || $this->passwordAccountId <= 0) {
			$this->resultText = 'Аккаунт не выбран.';
			return;
		}

		$newPassword = trim($this->newPassword);

		if ($newPassword === '') {
			$this->resultText = 'Пароль обязателен.';
			return;
		}

		if ($this->passwordMode === 'recover_stolen') {
			$account = \App\Domain\Accounts\Models\Account::query()->find($this->passwordAccountId);

			if ($account === null || (int) $account->assigned_to_telegram_id !== $telegramId) {
				$this->resultText = 'Доступ запрещен.';
				return;
			}

			$service->recoverStolen($this->passwordAccountId, $newPassword, $telegramId, [
				'source' => 'webapp',
			]);

			$this->resultText = sprintf('STOLEN восстановлен (аккаунт #%d).', $this->passwordAccountId);
		} else {
			$service->updatePassword($this->passwordAccountId, $newPassword, $telegramId, [
				'source' => 'webapp',
			]);

			$this->resultText = sprintf('Пароль обновлён (аккаунт #%d).', $this->passwordAccountId);
		}

		$this->passwordAccountId = null;
		$this->newPassword = '';
		$this->passwordMode = 'update';

		$this->loadHistory($telegramId);
		$this->loadStolen($telegramId);
		$this->tab = 'history';
	}

	public function postponeStolen(int $accountId, AccountStatusService $service): void
	{
		$telegramId = $this->telegramId();

		if ($telegramId <= 0) {
			$this->resultText = 'WebApp не инициализирован.';
			return;
		}

		$account = \App\Domain\Accounts\Models\Account::query()->find($accountId);

		if ($account === null || (int) $account->assigned_to_telegram_id !== $telegramId) {
			$this->resultText = 'Доступ запрещен.';
			return;
		}

		$ok = $service->extendDeadline($accountId, 1, $telegramId, [
			'source' => 'webapp',
			'action' => 'postpone',
		]);

		$this->resultText = $ok
			? sprintf('STOLEN перенесён на 1 день (аккаунт #%d).', $accountId)
			: 'Не удалось перенести.';

		$this->loadStolen($telegramId);
	}

	private function loadHistory(int $telegramId): void
	{
		$this->history = Issuance::query()
			->with(['account'])
			->where('telegram_id', $telegramId)
			->orderByDesc('issued_at')
			->limit(20)
			->get();
	}

	private function loadStolen(int $telegramId): void
	{
		$this->stolenAccounts = \App\Domain\Accounts\Models\Account::query()
			->where('status', \App\Domain\Accounts\Enums\AccountStatus::STOLEN)
			->where('assigned_to_telegram_id', $telegramId)
			->orderBy('status_deadline_at')
			->limit(50)
			->get();
	}

	/**
	 * @param array<int, array{account_id:int, login:string, password:string}> $items
	 */
	private function formatIssuanceItems(array $items): string
	{
		if (count($items) === 0) {
			return 'Готово.';
		}

		if (count($items) === 1) {
			return sprintf(
				"Выдано:\nЛогин: %s\nПароль: %s",
				(string) $items[0]['login'],
				(string) $items[0]['password']
			);
		}

		$lines = ['Выдано (x'.count($items).')'];

		foreach ($items as $index => $item) {
			$lines[] = sprintf(
				"#%d\nЛогин: %s\nПароль: %s",
				$index + 1,
				(string) $item['login'],
				(string) $item['password']
			);
		}

		return implode("\n\n", $lines);
	}

	private function telegramId(): int
	{
		return (int) session()->get('webapp.telegram_id', 0);
	}

	public function canDevBootstrap(): bool
	{
		return (bool) config('accesshub.webapp.verify_init_data', false) === false;
	}

	public function lastEventTypeFor(int $accountId): ?string
	{
		return AccountEvent::query()
			->where('account_id', $accountId)
			->orderByDesc('id')
			->value('type');
	}

	public function render()
	{
		return view('webapp.page', [
			'isBootstrapped' => $this->telegramId() > 0,
			'canDevBootstrap' => $this->canDevBootstrap(),
		]);
	}
}

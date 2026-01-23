<?php

declare(strict_types=1);

namespace App\WebApp\Livewire;

use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Accounts\Services\AccountStatusService;
use Illuminate\Support\Collection;
use Livewire\Component;

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

	// Password update form (shown per row)
	public ?int $passwordAccountId = null;
	public string $newPassword = '';

	public function mount(): void
	{
		$this->history = collect();

		$telegramId = $this->telegramId();

		if ($telegramId > 0) {
			$this->loadHistory($telegramId);
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
			}
		}
	}

	public function issue(IssueService $service): void
	{
		$telegramId = $this->telegramId();

		if ($telegramId <= 0) {
			$this->resultText = 'WebApp not bootstrapped. Open inside Telegram and try again.';
			return;
		}

		$orderId = trim($this->orderId);
		$platform = trim($this->platform);
		$game = trim($this->game);
		$qty = max(1, (int) $this->qty);

		if ($orderId === '' || $platform === '' || $game === '') {
			$this->resultText = 'Please fill all fields.';
			return;
		}

		$result = $service->issue($telegramId, $orderId, $game, $platform, $qty);

		if ($result->ok() !== true) {
			$this->resultText = (string) ($result->message() ?? 'Error.');
			$this->loadHistory($telegramId);
			return;
		}

		$this->resultText = sprintf(
			"OK\nLogin: %s\nPassword: %s",
			(string) $result->login,
			(string) $result->password
		);

		$this->loadHistory($telegramId);
		$this->tab = 'history';
	}

	public function markProblem(int $accountId, string $reason, AccountStatusService $service): void
	{
		$telegramId = $this->telegramId();

		if ($telegramId <= 0) {
			$this->resultText = 'WebApp not bootstrapped.';
			return;
		}

		$service->markProblem($accountId, $telegramId, $reason, [
			'source' => 'webapp',
		]);

		$this->resultText = sprintf('Problem saved: %s (account #%d).', $reason, $accountId);

		$this->loadHistory($telegramId);
		$this->tab = 'history';
	}

	public function openPasswordForm(int $accountId): void
	{
		$this->passwordAccountId = $accountId;
		$this->newPassword = '';
		$this->resultText = null;
	}

	public function cancelPasswordForm(): void
	{
		$this->passwordAccountId = null;
		$this->newPassword = '';
	}

	public function submitPassword(AccountStatusService $service): void
	{
		$telegramId = $this->telegramId();

		if ($telegramId <= 0) {
			$this->resultText = 'WebApp not bootstrapped.';
			return;
		}

		if ($this->passwordAccountId === null || $this->passwordAccountId <= 0) {
			$this->resultText = 'No account selected.';
			return;
		}

		$newPassword = trim($this->newPassword);

		if ($newPassword === '') {
			$this->resultText = 'Password is required.';
			return;
		}

		$service->updatePassword($this->passwordAccountId, $newPassword, $telegramId);

		$this->resultText = sprintf('Password updated (account #%d).', $this->passwordAccountId);

		$this->passwordAccountId = null;
		$this->newPassword = '';

		$this->loadHistory($telegramId);
		$this->tab = 'history';
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
		])->layout('layouts.app');
	}
}
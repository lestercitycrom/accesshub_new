<?php

declare(strict_types=1);

namespace App\WebApp\Livewire;

use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
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

	private function loadHistory(int $telegramId): void
	{
		$this->history = Issuance::query()
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

	public function render()
	{
		return view('webapp.page', [
			'isBootstrapped' => $this->telegramId() > 0,
			'canDevBootstrap' => $this->canDevBootstrap(),
		])->layout('layouts.app');
	}
}
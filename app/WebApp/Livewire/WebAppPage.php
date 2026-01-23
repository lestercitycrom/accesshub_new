<?php

declare(strict_types=1);

namespace App\WebApp\Livewire;

use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use Illuminate\Support\Collection;
use Livewire\Component;

final class WebAppPage extends Component
{
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

		$telegramId = (int) session()->get('webapp.telegram_id', 0);

		if ($telegramId > 0) {
			$this->loadHistory($telegramId);
		}
	}

	public function issue(IssueService $service): void
	{
		$telegramId = (int) session()->get('webapp.telegram_id', 0);

		if ($telegramId <= 0) {
			$this->resultText = 'WebApp not bootstrapped. Open inside Telegram and try again.';
			return;
		}

		$orderId = trim($this->orderId);
		$platform = trim($this->platform);
		$game = trim($this->game);

		if ($orderId === '' || $platform === '' || $game === '') {
			$this->resultText = 'Please fill all fields.';
			return;
		}

		$result = $service->issue($telegramId, $orderId, $game, $platform, max(1, (int) $this->qty));

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
	}

	private function loadHistory(int $telegramId): void
	{
		$this->history = Issuance::query()
			->where('telegram_id', $telegramId)
			->orderByDesc('issued_at')
			->limit(20)
			->get();
	}

	public function render()
	{
		return view('webapp.page');
	}
}
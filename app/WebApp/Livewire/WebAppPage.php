<?php

declare(strict_types=1);

namespace App\WebApp\Livewire;

use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Issuance\DTO\IssuanceResult;
use Livewire\Component;

final class WebAppPage extends Component
{
	public string $orderId = '';
	public string $game = '';
	public string $platform = '';
	public int $qty = 1;

	public ?string $resultText = null;
	public $history = null;

	protected $rules = [
		'orderId' => 'required|string|max:100',
		'game' => 'required|in:cs2,dota2,pubg',
		'platform' => 'required|in:steam,epic',
		'qty' => 'required|integer|min:1|max:2',
	];

	public function mount()
	{
		$this->loadHistory();
	}

	public function submit()
	{
		$this->validate();

		$telegramId = session('webapp.telegram_id');

		if (!$telegramId) {
			$this->resultText = 'Ошибка: сессия не инициализирована. Выполните bootstrap.';
			return;
		}

		$service = app(IssueService::class);
		$result = $service->issue(
			telegramId: $telegramId,
			orderId: $this->orderId,
			game: $this->game,
			platform: $this->platform,
			qty: $this->qty,
		);

		if ($result->success) {
			$this->resultText = sprintf(
				"✅ Аккаунт выдан!\n\nЛогин: %s\nПароль: %s",
				(string) $result->login,
				(string) $result->password
			);

			// Reset form
			$this->orderId = '';
			$this->game = '';
			$this->platform = '';
			$this->qty = 1;
		} else {
			$this->resultText = '❌ Ошибка: ' . $result->error;
		}

		$this->loadHistory();
	}

	public function getIsBootstrappedProperty(): bool
	{
		return session()->has('webapp.telegram_id');
	}

	private function loadHistory(): void
	{
		$telegramId = session('webapp.telegram_id');

		if (!$telegramId) {
			$this->history = collect();
			return;
		}

		$this->history = Issuance::query()
			->where('telegram_id', $telegramId)
			->orderByDesc('issued_at')
			->limit(config('accesshub.webapp.max_history_items', 20))
			->get();
	}

	public function render()
	{
		return view('webapp.page');
	}
}
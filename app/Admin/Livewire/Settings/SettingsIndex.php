<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Settings;

use App\Domain\Settings\Services\SettingsService;
use App\Telegram\Services\TelegramClient;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class SettingsIndex extends Component
{
	public int $cooldownDays = 30;
	public int $stolenDefaultDeadlineDays = 5;
	public int $maxQty = 3;
	public string $webappMenuUrl = '';
	public string $webappMenuText = '';
	public string $webappIssueDelivery = 'both';

	/** Success message shown after save (visible in Livewire response) */
	public ?string $successMessage = null;

	public function mount(SettingsService $settings): void
	{
		Gate::authorize('admin');

		$this->cooldownDays = $settings->getInt('cooldown_days', 30);
		$this->stolenDefaultDeadlineDays = $settings->getInt('stolen_default_deadline_days', 5);
		$this->maxQty = $settings->getInt('max_qty', 3);

		$defaultUrl = rtrim((string) config('app.url'), '/') . '/webapp';
		$this->webappMenuUrl = (string) ($settings->get('webapp_menu_url') ?? $defaultUrl);
		$this->webappMenuText = (string) ($settings->get('webapp_menu_text') ?? 'Открыть WebApp');
		$this->webappIssueDelivery = (string) ($settings->get('webapp_issue_delivery') ?? 'both');
		if (!in_array($this->webappIssueDelivery, ['webapp', 'chat', 'both'], true)) {
			$this->webappIssueDelivery = 'both';
		}
	}

	public function save(SettingsService $settings): void
	{
		Gate::authorize('admin');

		$this->validate([
			'cooldownDays' => ['required', 'integer', 'min:0', 'max:3650'],
			'stolenDefaultDeadlineDays' => ['required', 'integer', 'min:1', 'max:365'],
			'maxQty' => ['required', 'integer', 'min:1', 'max:100'],
			'webappIssueDelivery' => ['required', 'in:webapp,chat,both'],
		]);

		$userId = (int) auth()->id();

		$settings->set('cooldown_days', (int) $this->cooldownDays, $userId);
		$settings->set('stolen_default_deadline_days', (int) $this->stolenDefaultDeadlineDays, $userId);
		$settings->set('max_qty', (int) $this->maxQty, $userId);
		$settings->set('webapp_issue_delivery', $this->webappIssueDelivery, $userId);

		$this->successMessage = 'Настройки сохранены.';
		session()->flash('status', 'Настройки сохранены.');
	}

	public function applyWebAppMenu(SettingsService $settings, TelegramClient $telegram): void
	{
		Gate::authorize('admin');

		$this->validate([
			'webappMenuUrl' => ['required', 'string', 'max:255'],
			'webappMenuText' => ['required', 'string', 'min:2', 'max:64'],
		]);

		$userId = (int) auth()->id();
		$settings->set('webapp_menu_url', trim($this->webappMenuUrl), $userId);
		$settings->set('webapp_menu_text', trim($this->webappMenuText), $userId);

		$ok = $telegram->setChatMenuButton(trim($this->webappMenuText), trim($this->webappMenuUrl));

		$this->successMessage = $ok ? 'Кнопка меню WebApp установлена.' : 'Не удалось установить кнопку меню WebApp.';
		session()->flash('status', $this->successMessage);
	}

	public function render()
	{
		return view('admin.settings.index')->layout('layouts.admin');
	}
}

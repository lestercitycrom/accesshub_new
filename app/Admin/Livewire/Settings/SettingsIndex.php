<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Settings;

use App\Domain\Settings\Services\SettingsService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class SettingsIndex extends Component
{
	public int $cooldownDays = 30;
	public int $stolenDefaultDeadlineDays = 5;
	public int $maxQty = 3;

	public function mount(SettingsService $settings): void
	{
		Gate::authorize('admin');

		$this->cooldownDays = $settings->getInt('cooldown_days', 30);
		$this->stolenDefaultDeadlineDays = $settings->getInt('stolen_default_deadline_days', 5);
		$this->maxQty = $settings->getInt('max_qty', 3);
	}

	public function save(SettingsService $settings): void
	{
		Gate::authorize('admin');

		$this->validate([
			'cooldownDays' => ['required', 'integer', 'min:0', 'max:3650'],
			'stolenDefaultDeadlineDays' => ['required', 'integer', 'min:1', 'max:365'],
			'maxQty' => ['required', 'integer', 'min:1', 'max:100'],
		]);

		$userId = (int) auth()->id();

		$settings->set('cooldown_days', (int) $this->cooldownDays, $userId);
		$settings->set('stolen_default_deadline_days', (int) $this->stolenDefaultDeadlineDays, $userId);
		$settings->set('max_qty', (int) $this->maxQty, $userId);

		session()->flash('status', 'Settings saved.');
	}

	public function render()
	{
		return view('admin.settings.index')->layout('layouts.admin');
	}
}
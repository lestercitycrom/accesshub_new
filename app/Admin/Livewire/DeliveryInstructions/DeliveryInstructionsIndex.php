<?php

declare(strict_types=1);

namespace App\Admin\Livewire\DeliveryInstructions;

use App\Delivery\Models\DeliveryPlatformInstruction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class DeliveryInstructionsIndex extends Component
{
	public string $platform = '';
	public string $title = '';
	public string $body = '';
	public bool $isActive = true;

	public function mount(): void
	{
		Gate::authorize('hub-supply');

		$this->selectPlatform((string) ($this->platformOptions[0] ?? ''));
	}

	public function selectPlatform(string $platform): void
	{
		Gate::authorize('hub-supply');

		if (!in_array($platform, $this->platformOptions, true)) {
			return;
		}

		$instruction = DeliveryPlatformInstruction::query()
			->where('platform', $platform)
			->first();

		$this->platform = $platform;
		$this->title = (string) ($instruction?->title ?? $platform . ' instructions');
		$this->body = (string) ($instruction?->body ?? '');
		$this->isActive = (bool) ($instruction?->is_active ?? true);
		$this->resetValidation();
	}

	public function save(): void
	{
		Gate::authorize('hub-supply');

		$data = $this->validate([
			'platform' => ['required', 'string', Rule::in($this->platformOptions)],
			'title' => ['required', 'string', 'min:2', 'max:255'],
			'body' => ['nullable', 'string', 'max:5000'],
			'isActive' => ['boolean'],
		]);

		DeliveryPlatformInstruction::query()->updateOrCreate(
			['platform' => (string) $data['platform']],
			[
				'title' => trim((string) $data['title']),
				'body' => trim((string) ($data['body'] ?? '')),
				'is_active' => (bool) $data['isActive'],
			],
		);

		session()->flash('message', 'Instruction saved.');
	}

	public function toggleActive(string $platform): void
	{
		Gate::authorize('hub-supply');

		$instruction = DeliveryPlatformInstruction::query()
			->where('platform', $platform)
			->first();

		if ($instruction === null) {
			return;
		}

		$instruction->forceFill(['is_active' => !$instruction->is_active])->save();
		$this->selectPlatform($platform);

		session()->flash('message', 'Instruction status updated.');
	}

	public function getPlatformOptionsProperty(): array
	{
		return collect((array) config('delivery.connection_platforms', []))
			->merge((array) config('delivery.direct_delivery_platforms', []))
			->merge(DeliveryPlatformInstruction::query()->pluck('platform'))
			->filter()
			->unique()
			->values()
			->all();
	}

	/**
	 * @return Collection<int, DeliveryPlatformInstruction>
	 */
	public function getInstructionsProperty(): Collection
	{
		$order = array_flip($this->platformOptions);

		return DeliveryPlatformInstruction::query()
			->get()
			->sortBy(fn (DeliveryPlatformInstruction $instruction): int => $order[$instruction->platform] ?? 999)
			->values();
	}

	public function render()
	{
		return view('admin.delivery-instructions.index', [
			'instructions' => $this->instructions,
			'platformOptions' => $this->platformOptions,
		])->layout('layouts.admin');
	}
}

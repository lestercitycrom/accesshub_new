<div class="space-y-4">
	<h1 class="text-xl font-semibold">Settings</h1>

	@if(session('status'))
		<div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">
			{{ session('status') }}
		</div>
	@endif

	<div class="rounded-lg bg-white p-4 shadow-sm space-y-4 max-w-xl">
		<div class="space-y-1">
			<label class="text-sm font-medium">cooldown_days</label>
			<input class="w-full rounded-md border-gray-300" type="number" min="0" wire:model="cooldownDays">
			@error('cooldownDays') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			<p class="text-xs text-gray-500">Сколько дней нельзя повторно выдавать аккаунт после выдачи.</p>
		</div>

		<div class="space-y-1">
			<label class="text-sm font-medium">stolen_default_deadline_days</label>
			<input class="w-full rounded-md border-gray-300" type="number" min="1" wire:model="stolenDefaultDeadlineDays">
			@error('stolenDefaultDeadlineDays') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			<p class="text-xs text-gray-500">Сколько дней даём на работу со STOLEN до возврата в пул.</p>
		</div>

		<div class="space-y-1">
			<label class="text-sm font-medium">max_qty</label>
			<input class="w-full rounded-md border-gray-300" type="number" min="1" wire:model="maxQty">
			@error('maxQty') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			<p class="text-xs text-gray-500">Максимальный qty в выдаче (если используется).</p>
		</div>

		<div class="flex items-center gap-2">
			<button class="rounded-md bg-black px-4 py-2 text-white" type="button" wire:click="save">Save</button>
		</div>
	</div>
</div>
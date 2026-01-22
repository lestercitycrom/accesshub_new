<div class="space-y-4">
	<h1 class="text-xl font-semibold">
		{{ $account ? 'Edit' : 'Create' }} Account
	</h1>

	<div class="rounded-lg bg-white p-4 shadow-sm space-y-3 max-w-xl">
		<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
			<div class="space-y-1">
				<label class="text-sm font-medium">Game</label>
				<input class="w-full rounded-md border-gray-300" type="text" wire:model="game" required>
				@error('game') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			</div>

			<div class="space-y-1">
				<label class="text-sm font-medium">Platform</label>
				<input class="w-full rounded-md border-gray-300" type="text" wire:model="platform" required>
				@error('platform') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			</div>
		</div>

		<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
			<div class="space-y-1">
				<label class="text-sm font-medium">Login</label>
				<input class="w-full rounded-md border-gray-300" type="text" wire:model="login" required>
				@error('login') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			</div>

			<div class="space-y-1">
				<label class="text-sm font-medium">Password</label>
				<input class="w-full rounded-md border-gray-300" type="password" wire:model="password" {{ $account ? '' : 'required' }}>
				@error('password') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
				@if($account)
					<div class="text-xs text-gray-500">Leave empty to keep current password</div>
				@endif
			</div>
		</div>

		<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
			<div class="space-y-1">
				<label class="text-sm font-medium">Status</label>
				<select class="w-full rounded-md border-gray-300" wire:model="status" required>
					@foreach($statusOptions as $status)
						<option value="{{ $status }}">{{ $status }}</option>
					@endforeach
				</select>
				@error('status') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			</div>

			<div class="space-y-1">
				<label class="text-sm font-medium">Assigned to Telegram ID</label>
				<input class="w-full rounded-md border-gray-300" type="number" wire:model="assignedToTelegramId">
				@error('assignedToTelegramId') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			</div>
		</div>

		<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
			<div class="space-y-1">
				<label class="text-sm font-medium">Flags (JSON)</label>
				<textarea class="w-full rounded-md border-gray-300" rows="3" wire:model="flags" placeholder='{"key": "value"}'></textarea>
				@error('flags') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			</div>

			<div class="space-y-1">
				<label class="text-sm font-medium">Meta (JSON)</label>
				<textarea class="w-full rounded-md border-gray-300" rows="3" wire:model="meta" placeholder='{"key": "value"}'></textarea>
				@error('meta') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			</div>
		</div>

		<div class="flex items-center gap-2">
			<button class="rounded-md bg-black px-4 py-2 text-white" type="button" wire:click="save">Save</button>
			<a class="rounded-md border px-4 py-2" href="{{ route('admin.accounts.index') }}">Cancel</a>
		</div>
	</div>
</div>
<div class="space-y-4">
	<h1 class="text-xl font-semibold">
		{{ $telegramUser ? 'Edit' : 'Create' }} Telegram User
	</h1>

	<div class="rounded-lg bg-white p-4 shadow-sm space-y-3 max-w-xl">
		<div class="space-y-1">
			<label class="text-sm font-medium">Telegram ID</label>
			<input class="w-full rounded-md border-gray-300" type="number" wire:model="telegramId">
			@error('telegramId') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
		</div>

		<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
			<div class="space-y-1">
				<label class="text-sm font-medium">Username</label>
				<input class="w-full rounded-md border-gray-300" type="text" wire:model="username">
			</div>
			<div class="space-y-1">
				<label class="text-sm font-medium">First name</label>
				<input class="w-full rounded-md border-gray-300" type="text" wire:model="firstName">
			</div>
			<div class="space-y-1">
				<label class="text-sm font-medium">Last name</label>
				<input class="w-full rounded-md border-gray-300" type="text" wire:model="lastName">
			</div>
		</div>

		<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
			<div class="space-y-1">
				<label class="text-sm font-medium">Role</label>
				<select class="w-full rounded-md border-gray-300" wire:model="role">
					<option value="operator">operator</option>
					<option value="admin">admin</option>
				</select>
				@error('role') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
			</div>

			<div class="flex items-center gap-2 pt-6">
				<input type="checkbox" wire:model="isActive">
				<span class="text-sm">Active</span>
			</div>
		</div>

		<div class="flex items-center gap-2">
			<button class="rounded-md bg-black px-4 py-2 text-white" type="button" wire:click="save">Save</button>
			<a class="rounded-md border px-4 py-2" href="{{ route('admin.telegram-users.index') }}">Cancel</a>
		</div>
	</div>
</div>
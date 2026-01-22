<div class="space-y-4">
	<div class="flex items-center justify-between">
		<h1 class="text-xl font-semibold">Telegram Users</h1>
		<a class="rounded-md bg-black px-4 py-2 text-white" href="{{ route('admin.telegram-users.create') }}">Create</a>
	</div>

	<div class="rounded-lg bg-white p-4 shadow-sm space-y-3">
		<div class="flex items-center gap-2">
			<input class="w-full max-w-sm rounded-md border-gray-300" type="text" placeholder="Search..." wire:model.live="q">

			<button class="rounded-md border px-3 py-2" type="button" wire:click="toggleActive(true)">Activate</button>
			<button class="rounded-md border px-3 py-2" type="button" wire:click="toggleActive(false)">Deactivate</button>

			<button class="rounded-md border px-3 py-2" type="button" wire:click="setRole('operator')">Role: operator</button>
			<button class="rounded-md border px-3 py-2" type="button" wire:click="setRole('admin')">Role: admin</button>
		</div>

		<div class="overflow-x-auto">
			<table class="min-w-full text-sm">
				<thead>
					<tr class="text-left text-gray-600">
						<th class="py-2 pr-3">
							<input type="checkbox"
								@if(count($selected) && count($selected) === $rows->count()) checked @endif
								wire:click="$set('selected', {{ $rows->pluck('id') }})">
						</th>
						<th class="py-2 pr-3">Telegram ID</th>
						<th class="py-2 pr-3">User</th>
						<th class="py-2 pr-3">Role</th>
						<th class="py-2 pr-3">Active</th>
						<th class="py-2 pr-3"></th>
					</tr>
				</thead>
				<tbody>
					@foreach($rows as $row)
						<tr class="border-t">
							<td class="py-2 pr-3">
								<input type="checkbox" value="{{ $row->id }}" wire:model="selected">
							</td>
							<td class="py-2 pr-3">{{ $row->telegram_id }}</td>
							<td class="py-2 pr-3">
								<div>{{ $row->username }}</div>
								<div class="text-xs text-gray-500">{{ $row->first_name }} {{ $row->last_name }}</div>
							</td>
							<td class="py-2 pr-3">{{ $row->role->value }}</td>
							<td class="py-2 pr-3">{{ $row->is_active ? 'yes' : 'no' }}</td>
							<td class="py-2 pr-3">
								<a class="text-sm underline" href="{{ route('admin.telegram-users.edit', $row) }}">Edit</a>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		{{ $rows->links() }}
	</div>
</div>
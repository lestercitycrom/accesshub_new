<div class="space-y-4">
	<div class="flex items-center justify-between">
		<h1 class="text-xl font-semibold">Accounts</h1>
		<a class="rounded-md bg-black px-4 py-2 text-white" href="{{ route('admin.accounts.create') }}">Create</a>
	</div>

	<div class="rounded-lg bg-white p-4 shadow-sm space-y-3">
		<div class="grid grid-cols-1 md:grid-cols-4 gap-3">
			<div>
				<label class="text-sm font-medium">Search</label>
				<input class="w-full rounded-md border-gray-300" type="text" placeholder="Login, game, platform..." wire:model.live="q">
			</div>

			<div>
				<label class="text-sm font-medium">Status</label>
				<select class="w-full rounded-md border-gray-300" wire:model.live="statusFilter">
					<option value="">All</option>
					@foreach($statusOptions as $status)
						<option value="{{ $status }}">{{ $status }}</option>
					@endforeach
				</select>
			</div>

			<div>
				<label class="text-sm font-medium">Game</label>
				<select class="w-full rounded-md border-gray-300" wire:model.live="gameFilter">
					<option value="">All</option>
					@foreach($gameOptions as $game)
						<option value="{{ $game }}">{{ $game }}</option>
					@endforeach
				</select>
			</div>

			<div>
				<label class="text-sm font-medium">Platform</label>
				<select class="w-full rounded-md border-gray-300" wire:model.live="platformFilter">
					<option value="">All</option>
					@foreach($platformOptions as $platform)
						<option value="{{ $platform }}">{{ $platform }}</option>
					@endforeach
				</select>
			</div>
		</div>

		<div class="flex items-center gap-2">
			@foreach($statusOptions as $status)
				<button class="rounded-md border px-3 py-2 text-xs" type="button" wire:click="setStatus('{{ $status }}')">Status: {{ $status }}</button>
			@endforeach
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
						<th class="py-2 pr-3">Game</th>
						<th class="py-2 pr-3">Platform</th>
						<th class="py-2 pr-3">Login</th>
						<th class="py-2 pr-3">Status</th>
						<th class="py-2 pr-3">Assigned To</th>
						<th class="py-2 pr-3"></th>
					</tr>
				</thead>
				<tbody>
					@foreach($rows as $row)
						<tr class="border-t">
							<td class="py-2 pr-3">
								<input type="checkbox" value="{{ $row->id }}" wire:model="selected">
							</td>
							<td class="py-2 pr-3">{{ $row->game }}</td>
							<td class="py-2 pr-3">{{ $row->platform }}</td>
							<td class="py-2 pr-3">{{ $row->login }}</td>
							<td class="py-2 pr-3">{{ $row->status->value }}</td>
							<td class="py-2 pr-3">{{ $row->assigned_to_telegram_id ?: '-' }}</td>
							<td class="py-2 pr-3 flex gap-2">
								<a class="text-sm underline" href="{{ route('admin.accounts.show', $row) }}">Open</a>
								<a class="text-sm underline" href="{{ route('admin.accounts.edit', $row) }}">Edit</a>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		{{ $rows->links() }}
	</div>
</div>
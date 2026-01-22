<div class="space-y-4">
	<h1 class="text-xl font-semibold">Account Lookup</h1>

	<div class="rounded-lg bg-white p-4 shadow-sm space-y-3">
		<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
			<div>
				<label class="text-sm font-medium">Search</label>
				<input class="w-full rounded-md border-gray-300" type="text" placeholder="Login, ID, Order ID..." wire:model.live="q">
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

			<div>
				<label class="text-sm font-medium">Assigned</label>
				<select class="w-full rounded-md border-gray-300" wire:model.live="assignedFilter">
					<option value="">All</option>
					<option value="assigned">Assigned</option>
					<option value="unassigned">Unassigned</option>
				</select>
			</div>

			<div class="flex items-end">
				<a href="{{ route('admin.accounts.create') }}" class="w-full rounded-md bg-black px-4 py-2 text-white text-center hover:bg-gray-800">
					Create
				</a>
			</div>
		</div>

		<div class="overflow-x-auto">
			<table class="min-w-full text-sm">
				<thead>
					<tr class="text-left text-gray-600 border-b">
						<th class="py-2 pr-3">ID</th>
						<th class="py-2 pr-3">Login</th>
						<th class="py-2 pr-3">Game</th>
						<th class="py-2 pr-3">Platform</th>
						<th class="py-2 pr-3">Status</th>
						<th class="py-2 pr-3">Assigned To</th>
						<th class="py-2 pr-3"></th>
					</tr>
				</thead>
				<tbody>
					@forelse($rows as $account)
						<tr class="border-b">
							<td class="py-2 pr-3">{{ $account->id }}</td>
							<td class="py-2 pr-3">{{ $account->login }}</td>
							<td class="py-2 pr-3">{{ $account->game }}</td>
							<td class="py-2 pr-3">{{ $account->platform }}</td>
							<td class="py-2 pr-3">
								<span class="px-2 py-1 text-xs rounded-full
									@if($account->status === \App\Domain\Accounts\Enums\AccountStatus::ACTIVE) bg-green-100 text-green-800
									@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::STOLEN) bg-red-100 text-red-800
									@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::RECOVERY) bg-yellow-100 text-yellow-800
									@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::TEMP_HOLD) bg-orange-100 text-orange-800
									@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::DEAD) bg-gray-100 text-gray-800
									@endif">
									{{ $account->status->value }}
								</span>
							</td>
							<td class="py-2 pr-3">
								@if($account->assigned_to_telegram_id)
									{{ $account->assigned_to_telegram_id }}
								@else
									-
								@endif
							</td>
							<td class="py-2 pr-3">
								<a class="text-blue-600 hover:text-blue-800 underline" href="{{ route('admin.accounts.show', $account) }}">Open</a>
							</td>
						</tr>
					@empty
						<tr>
							<td class="py-4 text-center text-gray-500" colspan="7">No accounts found</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		{{ $rows->links() }}
	</div>
</div>
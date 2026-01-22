<div class="space-y-6">
	@if(session('message'))
		<div class="rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
			{{ session('message') }}
		</div>
	@endif

	<!-- Header -->
	<div class="flex items-center justify-between">
		<div>
			<h1 class="text-xl font-semibold">{{ $account->login }}</h1>
			<p class="text-sm text-gray-600">{{ $account->game }} / {{ $account->platform }}</p>
		</div>
		<div class="flex gap-2">
			<a href="{{ route('admin.accounts.edit', $account) }}" class="rounded-md border px-4 py-2 hover:bg-gray-50">Edit</a>
			<a href="{{ route('admin.account-lookup') }}" class="rounded-md border px-4 py-2 hover:bg-gray-50">Back to Search</a>
		</div>
	</div>

	<!-- Account Info -->
	<div class="rounded-lg bg-white p-6 shadow-sm">
		<h2 class="text-lg font-medium mb-4">Account Information</h2>
		<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
			<div class="space-y-3">
				<div>
					<label class="text-sm font-medium text-gray-600">ID</label>
					<div class="text-sm">{{ $account->id }}</div>
				</div>
				<div>
					<label class="text-sm font-medium text-gray-600">Login</label>
					<div class="font-mono text-sm">{{ $account->login }}</div>
				</div>
				<div>
					<label class="text-sm font-medium text-gray-600">Game</label>
					<div class="text-sm">{{ $account->game }}</div>
				</div>
				<div>
					<label class="text-sm font-medium text-gray-600">Platform</label>
					<div class="text-sm">{{ $account->platform }}</div>
				</div>
			</div>

			<div class="space-y-3">
				<div>
					<label class="text-sm font-medium text-gray-600">Status</label>
					<div>
						<span class="px-2 py-1 text-xs rounded-full
							@if($account->status === \App\Domain\Accounts\Enums\AccountStatus::ACTIVE) bg-green-100 text-green-800
							@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::STOLEN) bg-red-100 text-red-800
							@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::RECOVERY) bg-yellow-100 text-yellow-800
							@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::TEMP_HOLD) bg-orange-100 text-orange-800
							@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::DEAD) bg-gray-100 text-gray-800
							@endif">
							{{ $account->status->value }}
						</span>
					</div>
				</div>

				@if($account->assigned_to_telegram_id)
					<div>
						<label class="text-sm font-medium text-gray-600">Assigned to Telegram ID</label>
						<div class="text-sm">{{ $account->assigned_to_telegram_id }}</div>
					</div>
				@endif

				@if($account->status_deadline_at)
					<div>
						<label class="text-sm font-medium text-gray-600">Deadline</label>
						<div class="text-sm {{ $account->status_deadline_at->isPast() ? 'text-red-600' : 'text-green-600' }}">
							{{ $account->status_deadline_at->format('d.m.Y H:i') }}
							@if($account->status_deadline_at->isPast())
								<span class="font-medium">(OVERDUE)</span>
							@else
								({{ $account->status_deadline_at->diffInDays() }} days left)
							@endif
						</div>
					</div>
				@endif

				@if($account->flags)
					<div>
						<label class="text-sm font-medium text-gray-600">Flags</label>
						<div class="text-sm">
							@foreach($account->flags as $key => $value)
								<span class="inline-block bg-gray-100 px-2 py-1 rounded text-xs mr-1 mb-1">
									{{ $key }}: {{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}
								</span>
							@endforeach
						</div>
					</div>
				@endif
			</div>
		</div>
	</div>

	<!-- Actions -->
	<div class="rounded-lg bg-white p-6 shadow-sm">
		<h2 class="text-lg font-medium mb-4">Actions</h2>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<!-- Set Status -->
			<div class="space-y-3">
				<h3 class="font-medium">Set Status</h3>
				<div class="flex gap-2">
					<select wire:model="setStatus" class="flex-1 rounded-md border-gray-300">
						<option value="">Select status</option>
						@foreach($statusOptions as $status)
							<option value="{{ $status }}">{{ $status }}</option>
						@endforeach
					</select>
					<button wire:click="applyStatus" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Apply</button>
				</div>
			</div>

			<!-- Release to Pool -->
			<div class="space-y-3">
				<h3 class="font-medium">Release to Pool</h3>
				<p class="text-sm text-gray-600">Reset assignment and deadline, set status to ACTIVE</p>
				<button wire:click="releaseToPool" class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
					Release to Pool
				</button>
			</div>

			<!-- Update Password -->
			<div class="space-y-3">
				<h3 class="font-medium">Update Password</h3>
				<div class="flex gap-2">
					<input type="password" wire:model="newPassword" placeholder="New password" class="flex-1 rounded-md border-gray-300">
					<button wire:click="updatePassword" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">Update</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Issuances -->
	<div class="rounded-lg bg-white p-6 shadow-sm">
		<h2 class="text-lg font-medium mb-4">Recent Issuances</h2>
		@if($issuances->count() > 0)
			<div class="overflow-x-auto">
				<table class="min-w-full text-sm">
					<thead>
						<tr class="text-left text-gray-600 border-b">
							<th class="py-2 pr-3">Issued At</th>
							<th class="py-2 pr-3">Order ID</th>
							<th class="py-2 pr-3">Operator</th>
							<th class="py-2 pr-3">Qty</th>
							<th class="py-2 pr-3">Cooldown Until</th>
						</tr>
					</thead>
					<tbody>
						@foreach($issuances as $issuance)
							<tr class="border-b">
								<td class="py-2 pr-3">{{ $issuance->issued_at?->format('d.m.Y H:i') }}</td>
								<td class="py-2 pr-3">{{ $issuance->order_id }}</td>
								<td class="py-2 pr-3">
									@if($issuance->telegramUser)
										{{ $issuance->telegramUser->username ?: $issuance->telegram_id }}
									@else
										{{ $issuance->telegram_id }}
									@endif
								</td>
								<td class="py-2 pr-3">{{ $issuance->qty }}</td>
								<td class="py-2 pr-3">
									@if($issuance->cooldown_until)
										{{ $issuance->cooldown_until->format('d.m.Y H:i') }}
									@else
										-
									@endif
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		@else
			<p class="text-gray-500">No issuances found</p>
		@endif
	</div>

	<!-- Events -->
	<div class="rounded-lg bg-white p-6 shadow-sm">
		<h2 class="text-lg font-medium mb-4">Recent Events</h2>
		@if($events->count() > 0)
			<div class="space-y-2">
				@foreach($events as $event)
					<div class="border rounded p-3">
						<div class="flex items-start justify-between">
							<div class="flex-1">
								<div class="flex items-center gap-2 mb-1">
									<span class="px-2 py-1 text-xs rounded-full bg-gray-100">{{ $event->type }}</span>
									<span class="text-sm text-gray-600">{{ $event->created_at?->format('d.m.Y H:i') }}</span>
									@if($event->telegramUser)
										<span class="text-sm text-gray-600">
											by {{ $event->telegramUser->username ?: $event->telegram_id }}
										</span>
									@elseif($event->telegram_id)
										<span class="text-sm text-gray-600">by {{ $event->telegram_id }}</span>
									@else
										<span class="text-sm text-gray-600">system</span>
									@endif
								</div>
								@if($event->payload)
									<pre class="text-xs whitespace-pre-wrap bg-gray-50 p-2 rounded">@json($event->payload)</pre>
								@endif
							</div>
						</div>
					</div>
				@endforeach
			</div>
		@else
			<p class="text-gray-500">No events found</p>
		@endif
	</div>
</div>
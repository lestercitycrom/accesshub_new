<div class="space-y-6">
	@if(session('message'))
		<div class="rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
			{{ session('message') }}
		</div>
	@endif

	<div class="flex flex-wrap items-start justify-between gap-3">
		<div>
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">
				Account #{{ $account->id }}
			</h1>

			<p class="text-sm text-slate-500">
				{{ $account->game }} / {{ $account->platform }} — <span class="font-semibold text-slate-900">{{ $account->login }}</span>
			</p>
		</div>

		<div class="flex flex-wrap items-center gap-2">
			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.accounts.edit', $account) }}">
				Edit
			</a>

			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.accounts.lookup') }}">
				Lookup
			</a>

			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.accounts.index') }}">
				Back
			</a>
		</div>
	</div>

	@php
		$st = $account->status->value;
		$badge = match($st) {
			'ACTIVE' => 'green',
			'RECOVERY' => 'amber',
			'STOLEN' => 'red',
			'DEAD' => 'red',
			'TEMP_HOLD' => 'blue',
			default => 'gray',
		};
	@endphp

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
		<!-- Summary -->
		<x-admin.card title="Summary">
			<div class="space-y-4">
				<div class="flex flex-wrap items-center gap-2">
					<x-admin.badge :variant="$badge">{{ $st }}</x-admin.badge>

					@if($account->assigned_to_telegram_id)
						<x-admin.badge variant="violet">Assigned: {{ $account->assigned_to_telegram_id }}</x-admin.badge>
					@else
						<x-admin.badge variant="gray">Assigned: —</x-admin.badge>
					@endif

					@if($account->status_deadline_at)
						<x-admin.badge variant="amber">
							Deadline: {{ $account->status_deadline_at->format('Y-m-d H:i') }}
						</x-admin.badge>
					@else
						<x-admin.badge variant="gray">Deadline: —</x-admin.badge>
					@endif
				</div>

				<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
					<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Game</div>
						<div class="font-semibold text-slate-900">{{ $account->game }}</div>
					</div>

					<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Platform</div>
						<div class="font-semibold text-slate-900">{{ $account->platform }}</div>
					</div>

					<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Login</div>
						<div class="font-semibold text-slate-900 break-all">{{ $account->login }}</div>
					</div>

					<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Password</div>
						<div class="font-semibold text-slate-900 break-all">
							{{ $account->password ? '••••••••' : '—' }}
						</div>
					</div>
				</div>

				@if(is_array($account->flags) && count($account->flags) > 0)
					<div class="space-y-2">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Flags</div>
						<div class="flex flex-wrap gap-2">
							@foreach($account->flags as $k => $v)
								@if($v)
									<x-admin.badge variant="blue">{{ $k }}</x-admin.badge>
								@endif
							@endforeach
						</div>
					</div>
				@endif
			</div>
		</x-admin.card>

		<!-- Actions -->
		<x-admin.card title="Actions">
			<div class="space-y-4">
				<div class="grid grid-cols-1 gap-3">
					<div class="space-y-1">
						<label class="text-xs font-semibold text-slate-700">Set status</label>
						<select wire:model="setStatus"
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
							@foreach($statuses as $s)
								<option value="{{ $s }}">{{ $s }}</option>
							@endforeach
						</select>
					</div>

					<x-admin.button variant="primary" size="md" wire:click="applyStatus">
						Apply status
					</x-admin.button>

					<x-admin.button variant="secondary" size="md" wire:click="releaseToPool">
						Release to pool
					</x-admin.button>
				</div>

				<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-3">
					<div class="text-sm font-semibold text-slate-900">Update password (admin)</div>

					<x-admin.input
						label="New password"
						type="text"
						placeholder="enter new password..."
						wire:model="newPassword"
					/>

					<x-admin.button variant="danger" size="md" wire:click="updatePassword">
						Update password
					</x-admin.button>

					<p class="text-xs text-slate-500">
						Сбросит PASSWORD_UPDATE_REQUIRED/ACTION_REQUIRED и вернёт статус в ACTIVE.
					</p>
				</div>
			</div>
		</x-admin.card>

		<!-- Notes -->
		<x-admin.card title="Notes">
			<div class="space-y-2 text-sm text-slate-600">
				<p>Здесь можно держать краткие правила для операторов/админа.</p>
				<ul class="list-disc pl-5 space-y-1">
					<li>STOLEN → назначение + дедлайн</li>
					<li>RECOVERY → обновить пароль</li>
					<li>Release to pool → очистка assignment/deadline/flags</li>
				</ul>
			</div>
		</x-admin.card>
	</div>

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
		<!-- Issuances -->
		<x-admin.card title="Issuances (last 20)">
			<div class="overflow-x-auto rounded-2xl border border-slate-200">
				<table class="min-w-full text-sm">
					<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
						<tr>
							<th class="px-4 py-3 text-left">Issued</th>
							<th class="px-4 py-3 text-left">Order</th>
							<th class="px-4 py-3 text-left">Operator</th>
							<th class="px-4 py-3 text-left">Qty</th>
							<th class="px-4 py-3 text-left">Cooldown</th>
						</tr>
					</thead>

					<tbody class="divide-y divide-slate-200 bg-white">
						@forelse($issuances as $i)
							<tr class="hover:bg-slate-50/70">
								<td class="px-4 py-3">
									<span class="font-medium text-slate-900">{{ $i->issued_at?->format('Y-m-d H:i') }}</span>
								</td>
								<td class="px-4 py-3 font-semibold text-slate-900">{{ $i->order_id }}</td>
								<td class="px-4 py-3">
									<div class="text-xs text-slate-500">{{ $i->telegram_id }}</div>
									<div class="font-semibold text-slate-900">{{ $i->telegramUser?->username ?? '-' }}</div>
								</td>
								<td class="px-4 py-3">{{ $i->qty }}</td>
								<td class="px-4 py-3">{{ $i->cooldown_until?->format('Y-m-d') ?? '—' }}</td>
							</tr>
						@empty
							<tr>
								<td class="px-4 py-10 text-center text-slate-500" colspan="5">
									No issuances
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</x-admin.card>

		<!-- Events -->
		<x-admin.card title="Account Events (last 50)">
			<div class="overflow-x-auto rounded-2xl border border-slate-200">
				<table class="min-w-full text-sm">
					<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
						<tr>
							<th class="px-4 py-3 text-left">At</th>
							<th class="px-4 py-3 text-left">Type</th>
							<th class="px-4 py-3 text-left">Actor</th>
							<th class="px-4 py-3 text-left">Payload</th>
						</tr>
					</thead>

					<tbody class="divide-y divide-slate-200 bg-white">
						@forelse($events as $e)
							<tr class="hover:bg-slate-50/70">
								<td class="px-4 py-3">
									<span class="font-medium text-slate-900">{{ $e->created_at?->format('Y-m-d H:i') }}</span>
								</td>
								<td class="px-4 py-3 font-semibold text-slate-900">{{ $e->type }}</td>
								<td class="px-4 py-3">{{ $e->telegram_id ?? '—' }}</td>
								<td class="px-4 py-3">
									<pre class="text-xs whitespace-pre-wrap text-slate-700">@json($e->payload)</pre>
								</td>
							</tr>
						@empty
							<tr>
								<td class="px-4 py-10 text-center text-slate-500" colspan="4">
									No events
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</x-admin.card>
	</div>
</div>
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
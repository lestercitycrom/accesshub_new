<div class="space-y-6">
	<x-admin.page-header
		title="Problems"
		subtitle="Проблемные аккаунты: STOLEN/RECOVERY/TEMP_HOLD/DEAD + массовые действия."
	>
		<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
			href="{{ route('admin.account-lookup') }}">
			Lookup
		</a>

		<x-admin.button variant="secondary" size="md" wire:click="clear">
			Reset
		</x-admin.button>
	</x-admin.page-header>

	<x-admin.card title="Tabs">
		<div class="flex flex-wrap items-center gap-2">
			@foreach($tabs as $t)
				@php $active = $tab === $t; @endphp
				<button
					type="button"
					wire:click="$set('tab', '{{ $t }}')"
					class="rounded-xl px-4 py-2 text-sm font-semibold transition
						{{ $active ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}"
				>
					{{ $t }}
				</button>
			@endforeach

			<div class="ml-2 text-xs text-slate-500">
				Selected: <span class="font-semibold text-slate-700">{{ is_array($selected ?? null) ? count($selected) : 0 }}</span>
			</div>
		</div>
	</x-admin.card>

	<x-admin.card title="Actions">
		<div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
			<x-admin.input
				label="Search login"
				placeholder="login contains..."
				wire:model.live="q"
			/>

			<div class="flex items-end gap-2">
				<div class="w-24">
					<x-admin.input label="Extend days" type="number" min="1" wire:model="extendDays" />
				</div>

				<x-admin.button variant="secondary" size="md" wire:click="extendDeadline">
					Extend deadline
				</x-admin.button>
			</div>

			<div class="flex items-end gap-2">
				<x-admin.button variant="secondary" size="md" wire:click="releaseToPool">
					Release to pool
				</x-admin.button>
			</div>

			<div class="flex items-end gap-2">
				<div class="flex flex-wrap gap-2">
					@foreach($statuses as $s)
						<button class="rounded-xl px-3 py-2 text-xs font-semibold border border-slate-200 bg-white hover:bg-slate-50"
							type="button"
							wire:click="setStatus('{{ $s }}')">
							Set {{ $s }}
						</button>
					@endforeach
				</div>
			</div>
		</div>

		<p class="mt-3 text-xs text-slate-500">
			Extend deadline применяется только к STOLEN. Release to pool очищает assignment/deadline/flags и ставит ACTIVE.
		</p>
	</x-admin.card>

	<x-admin.card title="List">
		<div class="overflow-x-auto rounded-2xl border border-slate-200">
			<table class="min-w-full text-sm">
				<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
					<tr>
						<th class="w-10 px-4 py-3"></th>
						<th class="px-4 py-3 text-left">ID</th>
						<th class="px-4 py-3 text-left">Game</th>
						<th class="px-4 py-3 text-left">Platform</th>
						<th class="px-4 py-3 text-left">Login</th>
						<th class="px-4 py-3 text-left">Status</th>
						<th class="px-4 py-3 text-left">Assigned</th>
						<th class="px-4 py-3 text-left">Deadline</th>
						<th class="px-4 py-3 text-right">Action</th>
					</tr>
				</thead>

				<tbody class="divide-y divide-slate-200 bg-white">
					@forelse($rows as $row)
						<tr class="hover:bg-slate-50/70">
							<td class="px-4 py-3">
								<input type="checkbox" value="{{ $row->id }}" wire:model="selected" class="rounded border-slate-300">
							</td>
							<td class="px-4 py-3 font-semibold text-slate-900">{{ $row->id }}</td>
							<td class="px-4 py-3">{{ $row->game }}</td>
							<td class="px-4 py-3">{{ $row->platform }}</td>
							<td class="px-4 py-3 font-semibold text-slate-900">{{ $row->login }}</td>
							<td class="px-4 py-3">
								<x-admin.status-badge :status="$row->status->value" />
							</td>
							<td class="px-4 py-3">
								@if($row->assigned_to_telegram_id)
									<x-admin.badge variant="violet">{{ $row->assigned_to_telegram_id }}</x-admin.badge>
								@else
									<span class="text-slate-400">—</span>
								@endif
							</td>
							<td class="px-4 py-3">
								@if($row->status_deadline_at)
									<span class="font-medium text-slate-900">{{ $row->status_deadline_at->format('Y-m-d H:i') }}</span>
								@else
									<span class="text-slate-400">—</span>
								@endif
							</td>
							<td class="px-4 py-3 text-right">
								<a class="text-sm font-semibold text-slate-900 hover:text-slate-700 underline"
									href="{{ route('admin.accounts.show', $row) }}">
									Open
								</a>
							</td>
						</tr>
					@empty
						<tr>
							<td class="px-4 py-10 text-center text-slate-500" colspan="9">No rows</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		@if(method_exists($rows, 'links'))
			<div class="pt-3">
				{{ $rows->links() }}
			</div>
		@endif
	</x-admin.card>
</div>
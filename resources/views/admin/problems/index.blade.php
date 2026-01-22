@php
	$activeFilters = 0;
	$activeFilters += !empty($q ?? null) ? 1 : 0;
	$activeFilters += !empty($tab ?? null) ? 1 : 0;
@endphp

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

		{{-- Density toggle --}}
		<div class="inline-flex rounded-xl border border-white/0 bg-white">
			<button type="button"
				wire:click="$set('density', 'normal')"
				class="rounded-l-xl px-3 py-2 text-xs font-semibold border border-slate-200 {{ ($density ?? 'normal') === 'normal' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
				Normal
			</button>
			<button type="button"
				wire:click="$set('density', 'compact')"
				class="rounded-r-xl px-3 py-2 text-xs font-semibold border-y border-r border-slate-200 {{ ($density ?? 'normal') === 'compact' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
				Compact
			</button>
		</div>
	</x-admin.page-header>

	@if(session('status'))
		<x-admin.alert variant="success" :message="session('status')" />
	@endif

	<x-admin.card title="Tabs">
		<div class="flex flex-wrap items-center gap-2">
			@foreach($tabs as $t)
				@php $active = ($tab ?? '') === $t; @endphp
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

	<x-admin.filters-panel title="Actions & Filters" :activeCount="$activeFilters">
		<div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
			<x-admin.input
				label="Search login"
				placeholder="login contains..."
				wire:model.live="q"
			/>

			<div class="flex items-end gap-2">
				<div class="w-28">
					<x-admin.input label="Extend days" type="number" min="1" wire:model="extendDays" />
				</div>

				<x-admin.button variant="secondary" size="md" wire:click="extendDeadline">
					Extend
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
	</x-admin.filters-panel>

	<x-admin.card title="List">
		<x-admin.table :density="($density ?? 'normal')" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th class="w-10"></x-admin.th>
					<x-admin.th>ID</x-admin.th>
					<x-admin.th>Game</x-admin.th>
					<x-admin.th>Platform</x-admin.th>
					<x-admin.th>Login</x-admin.th>
					<x-admin.th>Status</x-admin.th>
					<x-admin.th>Assigned</x-admin.th>
					<x-admin.th>Deadline</x-admin.th>
					<x-admin.th align="right">Action</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $row)
				<tr class="hover:bg-slate-50/70">
					<x-admin.td>
						<input type="checkbox" value="{{ $row->id }}" wire:model="selected" class="rounded border-slate-300">
					</x-admin.td>

					<x-admin.td class="font-semibold text-slate-900">{{ $row->id }}</x-admin.td>
					<x-admin.td>{{ $row->game }}</x-admin.td>
					<x-admin.td>{{ $row->platform }}</x-admin.td>
					<x-admin.td class="font-semibold text-slate-900">{{ $row->login }}</x-admin.td>

					<x-admin.td>
						<x-admin.status-badge :status="$row->status->value" />
					</x-admin.td>

					<x-admin.td>
						@if($row->assigned_to_telegram_id)
							<x-admin.badge variant="violet">{{ $row->assigned_to_telegram_id }}</x-admin.badge>
						@else
							<span class="text-slate-400">—</span>
						@endif
					</x-admin.td>

					<x-admin.td>
						@if($row->status_deadline_at)
							<span class="font-medium text-slate-900">{{ $row->status_deadline_at->format('Y-m-d H:i') }}</span>
						@else
							<span class="text-slate-400">—</span>
						@endif
					</x-admin.td>

					<x-admin.td align="right">
						<a class="text-sm font-semibold text-slate-900 hover:text-slate-700 underline"
							href="{{ route('admin.accounts.show', $row) }}">
							Open
						</a>
					</x-admin.td>
				</tr>
			@empty
				<tr>
					<td class="px-4 py-10 text-center text-slate-500" colspan="9">No rows</td>
				</tr>
			@endforelse
		</x-admin.table>

		@if(method_exists($rows, 'links'))
			<div class="pt-3">
				{{ $rows->links() }}
			</div>
		@endif
	</x-admin.card>
</div>
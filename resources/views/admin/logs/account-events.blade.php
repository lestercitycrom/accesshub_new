<div class="space-y-6">
	<x-admin.page-header
		title="Events"
		subtitle="Журнал событий аккаунтов: фильтры и быстрый переход к аккаунту."
	>
		<x-admin.button variant="secondary" size="md" wire:click="clearFilters">
			Clear
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

	<x-admin.card title="Filters">
		<div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
			<x-admin.input label="Account ID" placeholder="123..." wire:model.live="accountId" />
			<x-admin.input label="Telegram ID" placeholder="111..." wire:model.live="telegramId" />
			<x-admin.input label="Type" placeholder="SET_STATUS..." wire:model.live="type" />

			<div class="space-y-1">
				<label class="text-xs font-semibold text-slate-700">Date from</label>
				<input type="date" wire:model.live="dateFrom"
					class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
			</div>

			<div class="space-y-1">
				<label class="text-xs font-semibold text-slate-700">Date to</label>
				<input type="date" wire:model.live="dateTo"
					class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
			</div>
		</div>
	</x-admin.card>

	<x-admin.card title="Log">
		<x-admin.table-toolbar title="Events" :density="($density ?? 'normal')" />

		<x-admin.table :density="($density ?? 'normal')" :zebra="true" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th>At</x-admin.th>
					<x-admin.th>Account</x-admin.th>
					<x-admin.th>Type</x-admin.th>
					<x-admin.th>Actor telegram_id</x-admin.th>
					<x-admin.th>Payload</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $r)
				<tr>
					<x-admin.td>
						<span class="font-medium text-slate-900">{{ $r->created_at?->format('Y-m-d H:i') }}</span>
					</x-admin.td>
					<x-admin.td>
						<a class="underline font-semibold text-slate-900 hover:text-slate-700"
							href="{{ route('admin.accounts.show', $r->account_id) }}">
							#{{ $r->account_id }}
						</a>
					</x-admin.td>
					<x-admin.td class="font-semibold text-slate-900">{{ $r->type }}</x-admin.td>
					<x-admin.td>{{ $r->telegram_id ?? '—' }}</x-admin.td>
					<x-admin.td>
						<pre class="text-xs whitespace-pre-wrap text-slate-700">@json($r->payload)</pre>
					</x-admin.td>
				</tr>
			@empty
				<tr>
					<td class="px-4 py-10 text-center text-slate-500" colspan="5">No rows</td>
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
<div class="space-y-6">
	<div class="flex flex-wrap items-center justify-between gap-3">
		<div>
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">Events</h1>
			<p class="text-sm text-slate-500">Журнал событий аккаунтов: фильтры и быстрый переход к аккаунту.</p>
		</div>

		<x-admin.button variant="secondary" size="md" wire:click="clearFilters">
			Clear
		</x-admin.button>
	</div>

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
		<div class="overflow-x-auto rounded-2xl border border-slate-200">
			<table class="min-w-full text-sm">
				<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
					<tr>
						<th class="px-4 py-3 text-left">At</th>
						<th class="px-4 py-3 text-left">Account</th>
						<th class="px-4 py-3 text-left">Type</th>
						<th class="px-4 py-3 text-left">Actor telegram_id</th>
						<th class="px-4 py-3 text-left">Payload</th>
					</tr>
				</thead>

				<tbody class="divide-y divide-slate-200 bg-white">
					@forelse($rows as $r)
						<tr class="hover:bg-slate-50/70">
							<td class="px-4 py-3">
								<span class="font-medium text-slate-900">{{ $r->created_at?->format('Y-m-d H:i') }}</span>
							</td>
							<td class="px-4 py-3">
								<a class="underline font-semibold text-slate-900 hover:text-slate-700"
									href="{{ route('admin.accounts.show', $r->account_id) }}">
									#{{ $r->account_id }}
								</a>
							</td>
							<td class="px-4 py-3 font-semibold text-slate-900">{{ $r->type }}</td>
							<td class="px-4 py-3">{{ $r->telegram_id ?? '—' }}</td>
							<td class="px-4 py-3">
								<pre class="text-xs whitespace-pre-wrap text-slate-700">@json($r->payload)</pre>
							</td>
						</tr>
					@empty
						<tr>
							<td class="px-4 py-10 text-center text-slate-500" colspan="5">No rows</td>
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
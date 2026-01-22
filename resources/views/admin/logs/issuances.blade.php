<div class="space-y-6">
	<div class="flex flex-wrap items-center justify-between gap-3">
		<div>
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">Issuances</h1>
			<p class="text-sm text-slate-500">Журнал выдач: фильтры, экспорт, переход к аккаунту.</p>
		</div>

		<div class="flex items-center gap-2">
			@if(isset($exportUrl))
				<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
					href="{{ $exportUrl }}">
					Export CSV
				</a>
			@endif

			<x-admin.button variant="secondary" size="md" wire:click="clearFilters">
				Clear
			</x-admin.button>
		</div>
	</div>

	<x-admin.card title="Filters">
		<div class="grid grid-cols-1 gap-3 lg:grid-cols-6">
			<x-admin.input label="Order ID" placeholder="ORD-..." wire:model.live="orderId" />
			<x-admin.input label="Telegram ID" placeholder="111..." wire:model.live="telegramId" />
			<x-admin.input label="Account ID" placeholder="123..." wire:model.live="accountId" />
			<x-admin.input label="Game" placeholder="cs2..." wire:model.live="game" />
			<x-admin.input label="Platform" placeholder="steam..." wire:model.live="platform" />

			<div class="grid grid-cols-2 gap-2">
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
		</div>
	</x-admin.card>

	<x-admin.card title="Log">
		<div class="overflow-x-auto rounded-2xl border border-slate-200">
			<table class="min-w-full text-sm">
				<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
					<tr>
						<th class="px-4 py-3 text-left">Issued</th>
						<th class="px-4 py-3 text-left">Order</th>
						<th class="px-4 py-3 text-left">Account</th>
						<th class="px-4 py-3 text-left">Operator</th>
						<th class="px-4 py-3 text-left">Game</th>
						<th class="px-4 py-3 text-left">Platform</th>
						<th class="px-4 py-3 text-left">Qty</th>
						<th class="px-4 py-3 text-left">Cooldown</th>
					</tr>
				</thead>

				<tbody class="divide-y divide-slate-200 bg-white">
					@forelse($rows as $r)
						<tr class="hover:bg-slate-50/70">
							<td class="px-4 py-3">
								<span class="font-medium text-slate-900">{{ $r->issued_at?->format('Y-m-d H:i') }}</span>
							</td>
							<td class="px-4 py-3 font-semibold text-slate-900">{{ $r->order_id }}</td>
							<td class="px-4 py-3">
								<a class="underline font-semibold text-slate-900 hover:text-slate-700"
									href="{{ route('admin.accounts.show', $r->account_id) }}">
									#{{ $r->account_id }}
								</a>
							</td>
							<td class="px-4 py-3">
								<div class="text-xs text-slate-500">{{ $r->telegram_id }}</div>
								<div class="font-semibold text-slate-900">{{ $r->telegramUser?->username ?? '-' }}</div>
							</td>
							<td class="px-4 py-3">{{ $r->game }}</td>
							<td class="px-4 py-3">{{ $r->platform }}</td>
							<td class="px-4 py-3">{{ $r->qty }}</td>
							<td class="px-4 py-3">{{ $r->cooldown_until?->format('Y-m-d') ?? '—' }}</td>
						</tr>
					@empty
						<tr>
							<td class="px-4 py-10 text-center text-slate-500" colspan="8">No rows</td>
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
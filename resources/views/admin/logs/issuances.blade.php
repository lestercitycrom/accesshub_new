<div class="space-y-6">
	<x-admin.page-header
		title="Issuances"
		subtitle="Журнал выдач: фильтры, экспорт, переход к аккаунту."
	>
		@if(isset($exportUrl))
			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ $exportUrl }}">
				Export CSV
			</a>
		@endif

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

	<x-admin.filters-bar>
		<div class="lg:col-span-2">
			<x-admin.filter-input
				label="Order ID"
				placeholder="ORD-..."
				icon="hash"
				wire:model.live="orderId"
			/>
		</div>

		<div class="lg:col-span-2">
			<x-admin.filter-input
				label="Telegram ID"
				placeholder="111..."
				icon="message-circle"
				wire:model.live="telegramId"
			/>
		</div>

		<div class="lg:col-span-2">
			<x-admin.filter-input
				label="Account ID"
				placeholder="123..."
				icon="user"
				wire:model.live="accountId"
			/>
		</div>

		<div class="lg:col-span-2">
			<x-admin.filter-input
				label="Game"
				placeholder="cs2..."
				icon="database"
				wire:model.live="game"
			/>
		</div>

		<div class="lg:col-span-2">
			<x-admin.filter-input
				label="Platform"
				placeholder="steam..."
				icon="database"
				wire:model.live="platform"
			/>
		</div>

		<div class="lg:col-span-2">
			<div class="space-y-1">
				<label class="text-[11px] font-semibold text-slate-600">Date from</label>
				<input type="date" wire:model.live="dateFrom"
					class="w-full rounded-xl border border-slate-200 bg-white/70 px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300">
			</div>
		</div>

		<div class="lg:col-span-12 flex items-center justify-between gap-2 pt-1">
			<div class="text-xs text-slate-500 flex items-center gap-2">
				<x-admin.icon name="filter" class="h-4 w-4" />
				<span>Filters apply instantly.</span>
			</div>

			<div class="flex items-center gap-2">
				<x-admin.button variant="secondary" size="sm" wire:click="$refresh">
					<span class="inline-flex items-center gap-2">
						<x-admin.icon name="refresh" class="h-4 w-4" />
						Refresh
					</span>
				</x-admin.button>
			</div>
		</div>
	</x-admin.filters-bar>

	<x-admin.card>
		<x-admin.table-toolbar :density="($density ?? 'normal')" :showDensity="true" />

		<x-admin.table :density="($density ?? 'normal')" :zebra="true" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th>Issued</x-admin.th>
					<x-admin.th>Order</x-admin.th>
					<x-admin.th>Account</x-admin.th>
					<x-admin.th>Operator</x-admin.th>
					<x-admin.th>Game</x-admin.th>
					<x-admin.th>Platform</x-admin.th>
					<x-admin.th>Qty</x-admin.th>
					<x-admin.th>Cooldown</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $r)
				<tr>
					<x-admin.td>
						<span class="font-medium text-slate-900">{{ $r->issued_at?->format('Y-m-d H:i') }}</span>
					</x-admin.td>
					<x-admin.td class="font-semibold text-slate-900">{{ $r->order_id }}</x-admin.td>
					<x-admin.td>
						<a class="underline font-semibold text-slate-900 hover:text-slate-700"
							href="{{ route('admin.accounts.show', $r->account_id) }}">
							#{{ $r->account_id }}
						</a>
					</x-admin.td>
					<x-admin.td>
						<div class="text-xs text-slate-500">{{ $r->telegram_id }}</div>
						<div class="font-semibold text-slate-900">{{ $r->telegramUser?->username ?? '-' }}</div>
					</x-admin.td>
					<x-admin.td>{{ $r->game }}</x-admin.td>
					<x-admin.td>{{ $r->platform }}</x-admin.td>
					<x-admin.td>{{ $r->qty }}</x-admin.td>
					<x-admin.td>{{ $r->cooldown_until?->format('Y-m-d') ?? '—' }}</x-admin.td>
				</tr>
			@empty
				<tr>
					<td class="px-4 py-10 text-center text-slate-500" colspan="8">No rows</td>
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
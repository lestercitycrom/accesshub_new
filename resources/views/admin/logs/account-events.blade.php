<div class="space-y-6">
	<x-admin.page-header
		title="События аккаунтов"
		subtitle="История событий аккаунтов: фильтры и детальные данные."
	>
		<x-admin.button variant="secondary" size="md" wire:click="clearFilters">
			Сброс
		</x-admin.button>

		{{-- Density toggle --}}
		<div class="inline-flex rounded-xl border border-white/0 bg-white">
			<button type="button"
				wire:click="$set('density', 'normal')"
				class="rounded-l-xl px-3 py-2 text-xs font-semibold border border-slate-200 {{ ($density ?? 'normal') === 'normal' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
				Обычная
			</button>
			<button type="button"
				wire:click="$set('density', 'compact')"
				class="rounded-r-xl px-3 py-2 text-xs font-semibold border-y border-r border-slate-200 {{ ($density ?? 'normal') === 'compact' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
				Компактная
			</button>
		</div>
	</x-admin.page-header>

	<x-admin.filters-bar>
		<div class="lg:col-span-2">
			<x-admin.filter-input
				label="ID аккаунта"
				placeholder="123..."
				icon="user"
				wire:model.live="accountId"
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
				label="Тип"
				placeholder="SET_STATUS..."
				icon="tag"
				wire:model.live="type"
			/>
		</div>

		<div class="lg:col-span-2">
			<div class="space-y-1">
				<label class="text-[11px] font-semibold text-slate-600">Дата от</label>
				<input type="date" wire:model.live="dateFrom"
					class="w-full rounded-xl border border-slate-200 bg-white/70 px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300">
			</div>
		</div>

		<div class="lg:col-span-4">
			<div class="space-y-1">
				<label class="text-[11px] font-semibold text-slate-600">Дата до</label>
				<input type="date" wire:model.live="dateTo"
					class="w-full rounded-xl border border-slate-200 bg-white/70 px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300">
			</div>
		</div>
		<div class="lg:col-span-12 flex items-center justify-end gap-2 pt-1">
			<x-admin.button variant="secondary" size="sm" wire:click="$refresh">
				<span class="inline-flex items-center gap-2">
					<x-admin.icon name="refresh" class="h-4 w-4" />
					Обновить
				</span>
			</x-admin.button>
		</div>
	</x-admin.filters-bar>

	<x-admin.card>
		<x-admin.table-toolbar :density="($density ?? 'normal')" :showDensity="true" />

		<x-admin.table :density="($density ?? 'normal')" :zebra="true" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th sortable :sorted="$sortBy === 'created_at'" :direction="$sortBy === 'created_at' ? $sortDirection : null" sortField="created_at">Дата</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'account_id'" :direction="$sortBy === 'account_id' ? $sortDirection : null" sortField="account_id">Аккаунт</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'type'" :direction="$sortBy === 'type' ? $sortDirection : null" sortField="type">Тип</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'telegram_id'" :direction="$sortBy === 'telegram_id' ? $sortDirection : null" sortField="telegram_id">Telegram ID</x-admin.th>
					<x-admin.th>Детали</x-admin.th>
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
					<td class="px-4 py-10 text-center text-slate-500" colspan="5">Событий нет</td>
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

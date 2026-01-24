@php
	$activeFilters = 0;
	$activeFilters += !empty($q) ? 1 : 0;
	$activeFilters += !empty($gameFilter) ? 1 : 0;
	$activeFilters += !empty($platformFilter) ? 1 : 0;
	$activeFilters += !empty($statusFilter) ? 1 : 0;
@endphp

<div class="space-y-6">
	<x-admin.page-header
		title="Аккаунты"
		subtitle="Поиск, фильтры, быстрый доступ к карточке и экспорт."
		:meta="'<span class=&quot;font-semibold text-slate-700&quot;>Подсказка:</span> используйте глобальный поиск сверху для быстрого поиска.'"
	>
		<x-admin.page-actions primaryLabel="Создать" primaryIcon="database" :primaryHref="route('admin.accounts.create')">
			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.account-lookup') }}">
				Lookup
			</a>

			@if(isset($exportUrl))
				<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
					href="{{ $exportUrl }}">
					Экспорт CSV
				</a>
			@endif
		</x-admin.page-actions>

		<x-slot:breadcrumbs>
			<span class="text-slate-500">Админ</span>
			<span class="px-1 text-slate-300">/</span>
			<span class="font-semibold text-slate-700">Аккаунты</span>
		</x-slot:breadcrumbs>
	</x-admin.page-header>

	@if(session('status'))
		<x-admin.alert variant="success" :message="session('status')" />
	@endif

	<x-admin.filters-bar>
		<div class="lg:col-span-3">
			<x-admin.filter-input
				label="Поиск"
				placeholder="login contains..."
				icon="search"
				wire:model.live="q"
			/>
		</div>

		<div class="lg:col-span-2">
			<x-admin.filter-input
				label="Игра"
				placeholder="cs2 / minecraft..."
				icon="database"
				wire:model.live="gameFilter"
			/>
		</div>

		<div class="lg:col-span-2">
			<x-admin.filter-input
				label="Платформа"
				placeholder="steam / xbox..."
				icon="database"
				wire:model.live="platformFilter"
			/>
		</div>

		<div class="lg:col-span-2">
			<x-admin.filter-select label="Статус" icon="list" wire:model.live="statusFilter">
				<option value="">Any</option>
				@foreach($statusOptions as $s)
					<option value="{{ $s }}">{{ $s }}</option>
				@endforeach>
			</x-admin.filter-select>
		</div>

		<div class="lg:col-span-3 flex items-end gap-2">
			<x-admin.button variant="secondary" size="sm" wire:click="clearFilters">Clear</x-admin.button>
		</div>

		<div class="lg:col-span-12 flex items-center justify-between gap-2 pt-1">
			<div class="text-xs text-slate-500 flex items-center gap-2">
				<x-admin.icon name="filter" class="h-4 w-4" />
				<span>Filters work on top of login search.</span>
			</div>
		</div>
	</x-admin.filters-bar>

	<x-admin.card title="Аккаунты">
		<x-admin.table density="normal" :sticky="true" :zebra="true">
			<x-slot:head>
				<tr>
					<x-admin.th>ID</x-admin.th>
					<x-admin.th>Игра</x-admin.th>
					<x-admin.th>Платформа</x-admin.th>
					<x-admin.th>Логин</x-admin.th>
					<x-admin.th>Статус</x-admin.th>
					<x-admin.th>Assigned</x-admin.th>
					<x-admin.th>Deadline</x-admin.th>
					<x-admin.th align="right">Action</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $row)
				<tr class="hover:bg-slate-50/70">
					<x-admin.td class="font-semibold text-slate-900">{{ $row->id }}</x-admin.td>
					<x-admin.td>{{ $row->game }}</x-admin.td>
					<x-admin.td>{{ $row->platform }}</x-admin.td>

					<x-admin.td>
						<div class="font-semibold text-slate-900">{{ $row->login }}</div>
						@if(is_array($row->meta) && isset($row->meta['email_login']))
							<div class="text-xs text-slate-500">{{ $row->meta['email_login'] }}</div>
						@endif
					</x-admin.td>

					<x-admin.td><x-admin.status-badge :status="$row->status->value" /></x-admin.td>

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

					<x-admin.td align="right" class="w-20">
						<x-admin.table-actions
							:viewHref="route('admin.accounts.show', $row)"
							:editHref="route('admin.accounts.edit', $row)"
						/>
					</x-admin.td>
				</tr>
			@empty
				<tr>
					<td class="px-4 py-10 text-center text-slate-500" colspan="8">No accounts found</td>
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

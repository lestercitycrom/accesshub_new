@php
	$activeFilters = 0;
	$activeFilters += !empty($q) ? 1 : 0;
	$activeFilters += !empty($gameFilter) ? 1 : 0;
	$activeFilters += !empty($platformFilter) ? 1 : 0;
	$activeFilters += !empty($statusFilter) ? 1 : 0;
@endphp

<div class="space-y-6">
	<x-admin.page-header
		title="Accounts"
		subtitle="Поиск, фильтры, быстрый доступ к карточке и экспорт."
		:meta="'<span class=&quot;font-semibold text-slate-700&quot;>Tip:</span> используйте Global Search сверху для быстрого lookup.'"
	>
		<x-admin.page-actions primaryLabel="Create" primaryIcon="database" :primaryHref="route('admin.accounts.create')">
			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.account-lookup') }}">
				Lookup
			</a>

			@if(isset($exportUrl))
				<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
					href="{{ $exportUrl }}">
					Export CSV
				</a>
			@endif
		</x-admin.page-actions>

		<x-slot:breadcrumbs>
			<span class="text-slate-500">Admin</span>
			<span class="px-1 text-slate-300">/</span>
			<span class="font-semibold text-slate-700">Accounts</span>
		</x-slot:breadcrumbs>
	</x-admin.page-header>

	@if(session('status'))
		<x-admin.alert variant="success" :message="session('status')" />
	@endif

	<x-admin.filters-panel title="Filters" :activeCount="$activeFilters">
		<div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
			<x-admin.input label="Search" placeholder="login contains..." wire:model.live="q" />
			<x-admin.input label="Game" placeholder="cs2 / minecraft / ..." wire:model.live="gameFilter" />
			<x-admin.input label="Platform" placeholder="steam / xbox / ..." wire:model.live="platformFilter" />

			<div class="space-y-1">
				<label class="text-xs font-semibold text-slate-700">Status</label>
				<select wire:model.live="statusFilter"
					class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
					<option value="">Any</option>
					@foreach($statusOptions as $s)
						<option value="{{ $s }}">{{ $s }}</option>
					@endforeach
				</select>
			</div>
		</div>

		<div class="mt-4 flex items-center gap-2">
			<x-admin.button variant="secondary" size="sm" wire:click="clearFilters">Clear</x-admin.button>
			<div class="text-xs text-slate-500">Фильтры работают поверх поиска по login.</div>
		</div>
	</x-admin.filters-panel>

	<x-admin.card title="Accounts">
		<x-admin.table density="normal" :sticky="true">
			<x-slot:head>
				<tr>
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

					<x-admin.td align="right">
						<a class="text-sm font-semibold text-slate-900 hover:text-slate-700 underline"
							href="{{ route('admin.accounts.show', $row) }}">
							Open
						</a>
						<span class="text-slate-300 px-1">|</span>
						<a class="text-sm font-semibold text-slate-900 hover:text-slate-700 underline"
							href="{{ route('admin.accounts.edit', $row) }}">
							Edit
						</a>
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
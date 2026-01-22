@php
	$activeFilters = 0;
	$activeFilters += !empty($q) ? 1 : 0;
	$activeFilters += !empty($game) ? 1 : 0;
	$activeFilters += !empty($platform) ? 1 : 0;
	$activeFilters += !empty($status) ? 1 : 0;
@endphp

<div class="space-y-6">
	<x-admin.page-header
		title="Accounts"
		subtitle="Поиск, фильтры, быстрый доступ к карточке и экспорт."
	>
		<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
			href="{{ route('admin.accounts.lookup') }}">
			Lookup
		</a>

		@if(isset($exportUrl))
			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ $exportUrl }}">
				Export CSV
			</a>
		@endif

		<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800"
			href="{{ route('admin.accounts.create') }}">
			Create
		</a>
	</x-admin.page-header>

	@if(session('status'))
		<x-admin.alert variant="success" :message="session('status')" />
	@endif

	<x-admin.filters-panel title="Filters" :activeCount="$activeFilters">
		<div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
			<x-admin.input label="Search" placeholder="login contains..." wire:model.live="q" />
			<x-admin.input label="Game" placeholder="cs2 / minecraft / ..." wire:model.live="game" />
			<x-admin.input label="Platform" placeholder="steam / xbox / ..." wire:model.live="platform" />

			<div class="space-y-1">
				<label class="text-xs font-semibold text-slate-700">Status</label>
				<select wire:model.live="status"
					class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
					<option value="">Any</option>
					@foreach($statuses as $s)
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
		<div class="overflow-x-auto rounded-2xl border border-slate-200">
			<table class="min-w-full text-sm">
				<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
					<tr>
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
							<td class="px-4 py-3 font-semibold text-slate-900">{{ $row->id }}</td>
							<td class="px-4 py-3">{{ $row->game }}</td>
							<td class="px-4 py-3">{{ $row->platform }}</td>
							<td class="px-4 py-3">
								<div class="font-semibold text-slate-900">{{ $row->login }}</div>
								@if(is_array($row->meta) && isset($row->meta['email_login']))
									<div class="text-xs text-slate-500">{{ $row->meta['email_login'] }}</div>
								@endif
							</td>

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
								<span class="text-slate-300 px-1">|</span>
								<a class="text-sm font-semibold text-slate-900 hover:text-slate-700 underline"
									href="{{ route('admin.accounts.edit', $row) }}">
									Edit
								</a>
							</td>
						</tr>
					@empty
						<tr>
							<td class="px-4 py-10 text-center text-slate-500" colspan="8">No accounts found</td>
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
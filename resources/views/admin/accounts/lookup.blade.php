<div class="space-y-6">
	<x-admin.page-header
		title="Account Lookup"
		subtitle="Поиск аккаунтов по различным критериям."
		:meta="'<span class=&quot;font-semibold text-slate-700&quot;>Tip:</span> Используйте фильтры для быстрого поиска нужных аккаунтов.'"
	>
		<x-admin.page-actions primaryLabel="Create" primaryIcon="plus" :primaryHref="route('admin.accounts.create')" />
	</x-admin.page-header>

<x-admin.filters-bar>
	<div class="lg:col-span-4">
		<x-admin.filter-input
			label="Search"
			placeholder="Login, ID, Order ID..."
			icon="search"
			wire:model.live="q"
		/>
	</div>

	<div class="lg:col-span-2">
		<x-admin.filter-select label="Status" icon="list" wire:model.live="statusFilter">
			<option value="">All</option>
			@foreach($statusOptions as $status)
				<option value="{{ $status }}">{{ $status }}</option>
			@endforeach>
		</x-admin.filter-select>
	</div>

	<div class="lg:col-span-2">
		<x-admin.filter-select label="Game" icon="database" wire:model.live="gameFilter">
			<option value="">All</option>
			@foreach($gameOptions as $game)
				<option value="{{ $game }}">{{ $game }}</option>
			@endforeach>
		</x-admin.filter-select>
	</div>

	<div class="lg:col-span-2">
		<x-admin.filter-select label="Platform" icon="database" wire:model.live="platformFilter">
			<option value="">All</option>
			@foreach($platformOptions as $platform)
				<option value="{{ $platform }}">{{ $platform }}</option>
			@endforeach>
		</x-admin.filter-select>
	</div>

	<div class="lg:col-span-2">
		<x-admin.filter-select label="Assigned" icon="users" wire:model.live="assignedFilter">
			<option value="">All</option>
			<option value="1">Assigned</option>
			<option value="0">Not assigned</option>
		</x-admin.filter-select>
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

			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800"
				href="{{ route('admin.accounts.create') }}">
				<span class="inline-flex items-center gap-2">
					<x-admin.icon name="plus" class="h-4 w-4" />
					Create
				</span>
			</a>
		</div>
	</div>
</x-admin.filters-bar>

	<x-admin.card>
		<x-admin.table-toolbar :density="($density ?? 'normal')" :showDensity="true">
			{{-- Quick actions for results --}}
		</x-admin.table-toolbar>

		<x-admin.table :density="($density ?? 'normal')" :zebra="true" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th>ID</x-admin.th>
					<x-admin.th>Login</x-admin.th>
					<x-admin.th>Game</x-admin.th>
					<x-admin.th>Platform</x-admin.th>
					<x-admin.th>Status</x-admin.th>
					<x-admin.th>Assigned To</x-admin.th>
					<x-admin.th align="right">Actions</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $account)
				<tr>
					<x-admin.td>{{ $account->id }}</x-admin.td>
					<x-admin.td class="font-semibold text-slate-900">{{ $account->login }}</x-admin.td>
					<x-admin.td>{{ $account->game }}</x-admin.td>
					<x-admin.td>{{ $account->platform }}</x-admin.td>
					<x-admin.td>
						<x-admin.status-badge :status="$account->status->value" />
					</x-admin.td>
					<x-admin.td>
						@if($account->assigned_to_telegram_id)
							{{ $account->assigned_to_telegram_id }}
						@else
							<span class="text-slate-400">—</span>
						@endif
					</x-admin.td>
					<x-admin.td align="right" class="w-20" nowrap>
						<x-admin.table-actions
							:viewHref="route('admin.accounts.show', $account)"
						/>
					</x-admin.td>
				</tr>
			@empty
				<tr>
					<td class="px-4 py-10 text-center text-slate-500" colspan="7">No accounts found</td>
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
<div class="space-y-6">
	<x-admin.page-header
		title="Account Lookup"
		subtitle="Поиск аккаунтов по различным критериям."
		:meta="'<span class=&quot;font-semibold text-slate-700&quot;>Tip:</span> Используйте фильтры для быстрого поиска нужных аккаунтов.'"
	>
		<x-admin.page-actions primaryLabel="Create" primaryIcon="plus" :primaryHref="route('admin.accounts.create')" />
	</x-admin.page-header>

	<x-admin.filters-bar title="Filters">
		<x-admin.input variant="filter" size="sm" label="Search" placeholder="Login, ID, Order ID..." wire:model.live="q" />
		<x-admin.select variant="filter" size="sm" label="Status" wire:model.live="statusFilter">
			<option value="">All</option>
			@foreach($statusOptions as $status)
				<option value="{{ $status }}">{{ $status }}</option>
			@endforeach>
		</x-admin.select>
		<x-admin.select variant="filter" size="sm" label="Game" wire:model.live="gameFilter">
			<option value="">All</option>
			@foreach($gameOptions as $game)
				<option value="{{ $game }}">{{ $game }}</option>
			@endforeach>
		</x-admin.select>
		<x-admin.select variant="filter" size="sm" label="Platform" wire:model.live="platformFilter">
			<option value="">All</option>
			@foreach($platformOptions as $platform)
				<option value="{{ $platform }}">{{ $platform }}</option>
			@endforeach>
		</x-admin.select>
		<x-admin.select variant="filter" size="sm" label="Assigned" wire:model.live="assignedFilter">
			<option value="">All</option>
			<option value="assigned">Assigned</option>
			<option value="unassigned">Unassigned</option>
		</x-admin.select>
	</x-admin.filters-bar>

	<x-admin.card title="Results">
		<x-admin.table-toolbar title="Results" :density="($density ?? 'normal')">
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
					<x-admin.td align="right">
						<x-admin.icon-button href="{{ route('admin.accounts.show', $account) }}" icon="eye" title="View" />
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
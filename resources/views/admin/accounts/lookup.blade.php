<div class="space-y-6">
	<x-admin.page-header
		title="Поиск аккаунтов"
		subtitle="Поиск аккаунтов по различным критериям."
		:meta="'<span class=&quot;font-semibold text-slate-700&quot;>Подсказка:</span> Используйте фильтры для быстрого поиска нужных аккаунтов.'"
	>
		<x-admin.page-actions primaryLabel="Создать" primaryIcon="plus" :primaryHref="route('admin.accounts.create')" />
	</x-admin.page-header>

<x-admin.filters-bar>
	<div class="lg:col-span-4">
		<x-admin.filter-input
			label="Поиск"
			placeholder="Логин, ID, номер заказа..."
			icon="search"
			wire:model.live="q"
		/>
	</div>

	<div class="lg:col-span-2">
		<x-admin.filter-select label="Статус" icon="list" wire:model.live="statusFilter">
			<option value="">Любой</option>
			@foreach($statusOptions as $status)
				<option value="{{ $status }}">{{ $status }}</option>
			@endforeach
		</x-admin.filter-select>
	</div>

	<div class="lg:col-span-2">
		<x-admin.filter-select label="Игра" icon="database" wire:model.live="gameFilter">
			<option value="">Любая</option>
			@foreach($gameOptions as $game)
				<option value="{{ $game }}">{{ $game }}</option>
			@endforeach
		</x-admin.filter-select>
	</div>

	<div class="lg:col-span-2">
		<x-admin.filter-select label="Платформа" icon="database" wire:model.live="platformFilter">
			<option value="">Любая</option>
			@foreach($platformOptions as $platform)
				<option value="{{ $platform }}">{{ $platform }}</option>
			@endforeach
		</x-admin.filter-select>
	</div>

	<div class="lg:col-span-2">
		<x-admin.filter-select label="Назначен" icon="users" wire:model.live="assignedFilter">
			<option value="">Любой</option>
			<option value="1">Да</option>
			<option value="0">Нет</option>
		</x-admin.filter-select>
	</div>

	<div class="lg:col-span-12 flex items-center justify-end gap-2 pt-1">
		<x-admin.button variant="secondary" size="sm" wire:click="$refresh">
			<span class="inline-flex items-center gap-2">
				<x-admin.icon name="refresh" class="h-4 w-4" />
				Обновить
			</span>
		</x-admin.button>

		<a class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800"
			href="{{ route('admin.accounts.create') }}">
			<span class="inline-flex items-center gap-2">
				<x-admin.icon name="plus" class="h-4 w-4" />
				Создать
			</span>
		</a>
	</div>
</x-admin.filters-bar>

	<x-admin.card>
		<x-admin.table-toolbar :density="($density ?? 'normal')" :showDensity="true">
			{{-- Quick actions for results --}}
		</x-admin.table-toolbar>

		<x-admin.table :density="($density ?? 'normal')" :zebra="true" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th sortable :sorted="$sortBy === 'id'" :direction="$sortBy === 'id' ? $sortDirection : null" sortField="id">ID</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'login'" :direction="$sortBy === 'login' ? $sortDirection : null" sortField="login">Логин</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'game'" :direction="$sortBy === 'game' ? $sortDirection : null" sortField="game">Игра</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'platform'" :direction="$sortBy === 'platform' ? $sortDirection : null" sortField="platform">Платформа</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'status'" :direction="$sortBy === 'status' ? $sortDirection : null" sortField="status">Статус</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'assigned_to_telegram_id'" :direction="$sortBy === 'assigned_to_telegram_id' ? $sortDirection : null" sortField="assigned_to_telegram_id">Назначен</x-admin.th>
					<x-admin.th align="right">Действия</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $account)
				<tr>
					<x-admin.td>{{ $account->id }}</x-admin.td>
					<x-admin.td class="font-semibold text-slate-900">{{ $account->login }}</x-admin.td>
					<x-admin.td>{{ $account->game }}</x-admin.td>
					<x-admin.td>
						@if(is_array($account->platform))
							<div class="flex flex-wrap gap-1">
								@foreach($account->platform as $p)
									<x-admin.badge variant="blue" class="text-xs">{{ $p }}</x-admin.badge>
								@endforeach
							</div>
						@else
							{{ $account->platform }}
						@endif
					</x-admin.td>
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
					<td class="px-4 py-10 text-center text-slate-500" colspan="7">Аккаунты не найдены</td>
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

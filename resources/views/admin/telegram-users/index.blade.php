<div class="space-y-6">
	<x-admin.page-header
		title="Пользователи Telegram"
		subtitle="Управление операторами/админами и их активностью."
	>
		<x-admin.page-actions primaryLabel="Добавить" primaryIcon="user-plus" :primaryHref="route('admin.telegram-users.create')">
			<x-admin.button variant="secondary" size="sm" wire:click="$refresh">
				<span class="inline-flex items-center gap-2">
					<x-admin.icon name="refresh" class="h-4 w-4" />
					Refresh
				</span>
			</x-admin.button>
		</x-admin.page-actions>
	</x-admin.page-header>

	<x-admin.filters-bar>
		<div class="lg:col-span-4">
			<x-admin.filter-input
				label="Поиск"
				placeholder="username / name / telegram id..."
				icon="search"
				wire:model.live="q"
			/>
		</div>

		<div class="lg:col-span-8 flex items-end gap-2">
			<x-admin.button variant="secondary" size="sm" wire:click="toggleActive(true)">
				Activate
			</x-admin.button>

			<x-admin.button variant="secondary" size="sm" wire:click="toggleActive(false)">
				Deactivate
			</x-admin.button>

			<x-admin.button variant="ghost" size="sm" wire:click="setRole('operator')">
				Роль: оператор
			</x-admin.button>

			<x-admin.button variant="ghost" size="sm" wire:click="setRole('admin')">
				Роль: админ
			</x-admin.button>

			<div class="ml-2 text-xs text-slate-500">
				Selected: <span class="font-semibold text-slate-700">{{ is_array($selected ?? null) ? count($selected) : 0 }}</span>
			</div>
		</div>

		<div class="lg:col-span-12 flex items-center justify-between gap-2 pt-1">
			<div class="text-xs text-slate-500 flex items-center gap-2">
				<x-admin.icon name="filter" class="h-4 w-4" />
				<span>Filters apply instantly.</span>
			</div>
		</div>
	</x-admin.filters-bar>

	<x-admin.card>
		<x-admin.table-toolbar :density="($density ?? 'normal')" :showDensity="true" />

		<x-admin.table :density="($density ?? 'normal')" :zebra="true" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th class="w-10">
						<input type="checkbox" class="rounded border-slate-300"
							@if(count($selected ?? []) && count($selected) === $rows->count()) checked @endif
							wire:click="$set('selected', {{ $rows->pluck('id') }})">
					</x-admin.th>
					<x-admin.th>Telegram ID</x-admin.th>
					<x-admin.th>Пользователь</x-admin.th>
					<x-admin.th>Роль</x-admin.th>
					<x-admin.th>Активен</x-admin.th>
					<x-admin.th align="right" class="w-20">Action</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $row)
				<tr>
					<x-admin.td>
						<input type="checkbox" value="{{ $row->id }}" wire:model="selected" class="rounded border-slate-300">
					</x-admin.td>

					<x-admin.td>
						<div class="font-semibold text-slate-900">{{ $row->telegram_id }}</div>
					</x-admin.td>

					<x-admin.td>
						<div class="font-semibold text-slate-900">
							{{ $row->username ?? '-' }}
						</div>
						<div class="text-xs text-slate-500">
							{{ trim(($row->first_name ?? '').' '.($row->last_name ?? '')) ?: '-' }}
						</div>
					</x-admin.td>

					<x-admin.td>
						<x-admin.badge :variant="($row->role->value ?? '') === 'admin' ? 'violet' : 'blue'">
							{{ $row->role->value ?? 'operator' }}
						</x-admin.badge>
					</x-admin.td>

					<x-admin.td>
						@if((bool) ($row->is_active ?? false))
							<x-admin.badge variant="green">yes</x-admin.badge>
						@else
							<x-admin.badge variant="red">no</x-admin.badge>
						@endif
					</x-admin.td>

					<x-admin.td align="right" class="w-20">
						<x-admin.table-actions
							:editHref="route('admin.telegram-users.edit', $row)"
						/>
					</x-admin.td>
				</tr>
			@empty
				<tr>
					<x-admin.td colspan="6" class="text-center py-10 text-slate-500">
						No users found
					</x-admin.td>
				</tr>
			@endforelse
		</x-admin.table>

		@if(method_exists($rows, 'links'))
			<div class="pt-3">
				{{ $rows->links() }}
			</div>
		@endif>
	</x-admin.card>
</div>

<div class="space-y-6">
	<x-admin.page-header
		title="Пользователи Telegram"
		subtitle="Справочник операторов/админов и их идентификаторы. Управляйте доступами и ролями."
	>
		<x-admin.page-actions primaryLabel="Добавить" primaryIcon="user-plus" :primaryHref="route('admin.telegram-users.create')">
				<x-admin.button variant="secondary" size="sm" wire:click="$refresh">
					<span class="inline-flex items-center gap-2">
						<x-admin.icon name="refresh" class="h-4 w-4" />
						Обновить
					</span>
				</x-admin.button>
		</x-admin.page-actions>
	</x-admin.page-header>

	<x-admin.filters-bar>
		<div class="lg:col-span-4">
			<x-admin.filter-input
				placeholder="username / имя / telegram id..."
				icon="search"
				wire:model.live.debounce.300ms="q"
			/>
		</div>

		<div class="lg:col-span-8 flex items-end gap-2">
			<x-admin.button variant="secondary" size="sm" wire:click="toggleActive(true)">
				Активировать выбранных
			</x-admin.button>

			<x-admin.button variant="secondary" size="sm" wire:click="toggleActive(false)">
				Отключить выбранных
			</x-admin.button>

			<x-admin.button
				variant="ghost"
				size="sm"
				wire:click="setRoleFilter('operator')"
				class="{{ ($roleFilter ?? '') === 'operator' ? 'bg-slate-100' : '' }}"
			>
				Оператор
			</x-admin.button>

			<x-admin.button
				variant="ghost"
				size="sm"
				wire:click="setRoleFilter('admin')"
				class="{{ ($roleFilter ?? '') === 'admin' ? 'bg-slate-100' : '' }}"
			>
				Админ
			</x-admin.button>
		</div>
	</x-admin.filters-bar>

	<x-admin.card>
		<x-admin.table-toolbar :density="($density ?? 'normal')" :showDensity="true" />

		<x-admin.table :density="($density ?? 'normal')" :zebra="true" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th class="w-10">
						<input type="checkbox" class="rounded border-slate-300"
							@if($rows->count() > 0 && count($selected ?? []) === $rows->count()) checked @endif
							wire:click="toggleSelectAll"
						>
					</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'telegram_id'" :direction="$sortBy === 'telegram_id' ? $sortDirection : null" sortField="telegram_id">Telegram ID</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'username'" :direction="$sortBy === 'username' ? $sortDirection : null" sortField="username">Пользователь</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'role'" :direction="$sortBy === 'role' ? $sortDirection : null" sortField="role">Роль</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'is_active'" :direction="$sortBy === 'is_active' ? $sortDirection : null" sortField="is_active">Статус</x-admin.th>
					<x-admin.th align="right" class="w-20">Действия</x-admin.th>
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
							<x-admin.badge variant="green">Активен</x-admin.badge>
						@else
							<x-admin.badge variant="red">Отключен</x-admin.badge>
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
						Пользователи не найдены
					</x-admin.td>
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

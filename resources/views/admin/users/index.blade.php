<div class="space-y-6">
	<x-admin.page-header
		title="Пользователи"
		subtitle="Веб-пользователи панели и их роли. Роль определяет доступ к разделам."
	>
		<x-admin.page-actions>
			<x-admin.button variant="secondary" size="sm" wire:click="$refresh">
				<span class="inline-flex items-center gap-2">
					<x-admin.icon name="refresh" class="h-4 w-4" />
					Обновить
				</span>
			</x-admin.button>
		</x-admin.page-actions>
	</x-admin.page-header>

	@if(session('message'))
		<x-admin.alert variant="success" :message="session('message')" />
	@endif
	@if(session('error'))
		<x-admin.alert variant="danger" :message="session('error')" />
	@endif

	<x-admin.filters-bar>
		<div class="lg:col-span-12">
			<x-admin.filter-input
				placeholder="имя или email..."
				icon="search"
				wire:model.live.debounce.300ms="q"
			/>
		</div>
	</x-admin.filters-bar>

	<p class="-mt-2 px-1 text-xs text-slate-500">
		<b>Администратор</b> — всё, включая настройки и пользователей ·
		<b>Менеджер</b> — аккаунты, ссылки, инструкции, экспорт (без системных) ·
		<b>Оператор</b> — только заказы/выдача · <b>Без доступа</b> — вход закрыт
	</p>

	<x-admin.card>
		<x-admin.table :zebra="true" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th sortable :sorted="$sortBy === 'name'" :direction="$sortBy === 'name' ? $sortDirection : null" sortField="name">Пользователь</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'email'" :direction="$sortBy === 'email' ? $sortDirection : null" sortField="email">Email</x-admin.th>
					<x-admin.th>2FA</x-admin.th>
					<x-admin.th class="w-56">Роль</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $row)
				<tr wire:key="user-{{ $row->id }}">
					<x-admin.td>
						<div class="font-semibold text-slate-900">{{ $row->name }}</div>
					</x-admin.td>

					<x-admin.td>
						<div class="text-slate-700">{{ $row->email }}</div>
					</x-admin.td>

					<x-admin.td>
						@if($row->two_factor_confirmed_at !== null)
							<x-admin.badge variant="green">Включена</x-admin.badge>
						@else
							<x-admin.badge variant="amber">Нет</x-admin.badge>
						@endif
					</x-admin.td>

					<x-admin.td class="w-56">
						<select
							wire:change="setRole({{ $row->id }}, $event.target.value)"
							class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
						>
							<option value="" @selected($row->role === null)>Без доступа</option>
							@foreach($roleOptions as $option)
								<option value="{{ $option['value'] }}" @selected(($row->role->value ?? null) === $option['value'])>
									{{ $option['label'] }}
								</option>
							@endforeach
						</select>
					</x-admin.td>
				</tr>
			@empty
				<tr>
					<x-admin.td colspan="4" class="text-center py-10 text-slate-500">
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

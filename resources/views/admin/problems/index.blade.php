@php
	$activeFilters = 0;
	$activeFilters += !empty($q ?? null) ? 1 : 0;
	$activeFilters += !empty($tab ?? null) ? 1 : 0;
@endphp

<div class="space-y-6">
<x-admin.page-header
	title="Проблемные"
	subtitle="Проблемные аккаунты: STOLEN/RECOVERY/TEMP_HOLD/DEAD + массовые действия."
	:meta="'Выбрано: <span class=&quot;font-semibold text-slate-700&quot;>'.(is_array($selected ?? null) ? count($selected) : 0).'</span>'"
>
	<x-admin.page-actions primaryLabel="Поиск" primaryIcon="search" :primaryHref="route('admin.account-lookup')">
		<x-admin.button variant="secondary" size="md" wire:click="clear">Сброс</x-admin.button>
	</x-admin.page-actions>

	<x-slot:breadcrumbs>
		<span class="text-slate-500">Админ</span>
		<span class="px-1 text-slate-300">/</span>
		<span class="font-semibold text-slate-700">Проблемные</span>
	</x-slot:breadcrumbs>
</x-admin.page-header>

	@if(session('status'))
		<x-admin.alert variant="success" :message="session('status')" />
	@endif

	<x-admin.card title="Вкладки">
		<div class="flex flex-wrap items-center gap-2">
			@foreach($tabs as $t)
				@php $active = ($tab ?? '') === $t; @endphp
				<button
					type="button"
					wire:click="$set('tab', '{{ $t }}')"
					class="rounded-xl px-4 py-2 text-sm font-semibold transition
						{{ $active ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}"
				>
					{{ $t }}
				</button>
			@endforeach

			<div class="ml-2 text-xs text-slate-500">
				Выбрано: <span class="font-semibold text-slate-700">{{ is_array($selected ?? null) ? count($selected) : 0 }}</span>
			</div>
		</div>
	</x-admin.card>

	<x-admin.filters-bar>
		<div class="lg:col-span-3">
			<x-admin.filter-input
				label="Поиск по логину"
				placeholder="логин содержит..."
				icon="search"
				wire:model.live="q"
			/>
		</div>

		<div class="lg:col-span-2">
			<div class="flex items-end gap-2">
				<div class="flex-1">
					<x-admin.filter-input label="Продлить на дней" type="number" min="1" wire:model="extendDays" />
				</div>
				<x-admin.button variant="secondary" size="sm" wire:click="extendDeadline">
					Продлить
				</x-admin.button>
			</div>
		</div>

		<div class="lg:col-span-2">
			<x-admin.button variant="secondary" size="sm" wire:click="releaseToPool" class="w-full">
				Вернуть в пул
			</x-admin.button>
		</div>

		<div class="lg:col-span-5">
			<div class="flex items-end gap-2">
				<div class="flex flex-wrap gap-2">
					@foreach($statuses as $s)
						<button class="rounded-xl px-3 py-2 text-xs font-semibold border border-slate-200 bg-white hover:bg-slate-50"
							type="button"
							wire:click="setStatus('{{ $s }}')">
							Установить {{ $s }}
						</button>
					@endforeach
				</div>
			</div>
		</div>
	</x-admin.filters-bar>

	<x-admin.card>
		<div class="text-xs text-slate-500 mb-3">
			Назначен — Telegram ID оператора, к которому закреплён аккаунт (обычно при STOLEN).
			Дедлайн — срок статуса STOLEN; продлевается кнопкой «Продлить».
		</div>
		<x-admin.table-toolbar :density="($density ?? 'normal')" :showDensity="true">
			<x-admin.button variant="secondary" size="sm" wire:click="releaseToPool">Вернуть</x-admin.button>
		</x-admin.table-toolbar>

		<x-admin.table :density="($density ?? 'normal')" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th class="w-10"></x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'id'" :direction="$sortBy === 'id' ? $sortDirection : null" sortField="id">ID</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'game'" :direction="$sortBy === 'game' ? $sortDirection : null" sortField="game">Игра</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'platform'" :direction="$sortBy === 'platform' ? $sortDirection : null" sortField="platform">Платформа</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'login'" :direction="$sortBy === 'login' ? $sortDirection : null" sortField="login">Логин</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'status'" :direction="$sortBy === 'status' ? $sortDirection : null" sortField="status">Статус</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'assigned_to_telegram_id'" :direction="$sortBy === 'assigned_to_telegram_id' ? $sortDirection : null" sortField="assigned_to_telegram_id">Назначен</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'status_deadline_at'" :direction="$sortBy === 'status_deadline_at' ? $sortDirection : null" sortField="status_deadline_at">Дедлайн</x-admin.th>
					<x-admin.th align="right">Действие</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $row)
				<tr class="hover:bg-slate-50/70">
					<x-admin.td>
						<input type="checkbox" value="{{ $row->id }}" wire:model="selected" class="rounded border-slate-300">
					</x-admin.td>

					<x-admin.td class="font-semibold text-slate-900">{{ $row->id }}</x-admin.td>
					<x-admin.td>{{ $row->game }}</x-admin.td>
					<x-admin.td>
						@if(is_array($row->platform))
							<div class="flex flex-wrap gap-1">
								@foreach($row->platform as $p)
									<x-admin.badge variant="blue" class="text-xs">{{ $p }}</x-admin.badge>
								@endforeach
							</div>
						@else
							{{ $row->platform }}
						@endif
					</x-admin.td>
					<x-admin.td class="font-semibold text-slate-900">{{ $row->login }}</x-admin.td>

					<x-admin.td>
						<x-admin.status-badge :status="$row->status->value" />
					</x-admin.td>

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
							Открыть
						</a>
					</x-admin.td>
				</tr>
			@empty
				<tr>
					<td class="px-4 py-10 text-center text-slate-500" colspan="9">Записей нет</td>
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

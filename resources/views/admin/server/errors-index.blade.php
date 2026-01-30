<div class="space-y-6">
	<x-admin.page-header
		title="Сервер — Ошибки"
		subtitle="Ошибки при взаимодействии с Telegram-ботом и WebApp. Оператору показывается номер обращения (#ID)."
	>
		<x-admin.page-actions>
			<x-admin.button variant="secondary" size="md" wire:click="clearFilters">
				Сброс
			</x-admin.button>
			<x-admin.button variant="secondary" size="md" wire:click="$refresh">
				Обновить
			</x-admin.button>
		</x-admin.page-actions>

		<x-slot:breadcrumbs>
			<span class="text-slate-500">Админ</span>
			<span class="px-1 text-slate-300">/</span>
			<span class="font-semibold text-slate-700">Сервер</span>
		</x-slot:breadcrumbs>
	</x-admin.page-header>

	<x-admin.filters-bar>
		<div class="lg:col-span-3">
			<x-admin.filter-input
				label="Поиск (ID, telegram_id, сообщение, класс)"
				placeholder="поиск..."
				icon="search"
				wire:model.live="q"
			/>
		</div>
		<div class="lg:col-span-2">
			<div class="space-y-1">
				<label class="text-xs font-semibold text-slate-700">Контекст</label>
				<select wire:model.live="contextFilter"
					class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
					<option value="">Все</option>
					<option value="webhook">Webhook (команды в чате)</option>
					<option value="webapp">WebApp (окно приложения)</option>
				</select>
			</div>
		</div>
	</x-admin.filters-bar>

	<x-admin.card>
		<div class="text-xs text-slate-500 mb-3">
			Оператору при ошибке показывается: «Произошла внутренняя ошибка сервера. Сообщите администратору номер обращения: #ID».
		</div>
		<x-admin.table density="normal" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th sortable :sorted="$sortBy === 'id'" :direction="$sortBy === 'id' ? $sortDirection : null" sortField="id">#</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'created_at'" :direction="$sortBy === 'created_at' ? $sortDirection : null" sortField="created_at">Дата</x-admin.th>
					<x-admin.th>Контекст</x-admin.th>
					<x-admin.th>Telegram ID</x-admin.th>
					<x-admin.th>Путь</x-admin.th>
					<x-admin.th>Сообщение</x-admin.th>
					<x-admin.th align="right">Действие</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $row)
				<tr class="hover:bg-slate-50/70">
					<x-admin.td class="font-semibold text-slate-900">#{{ $row->id }}</x-admin.td>
					<x-admin.td>
						<span class="font-medium text-slate-900">{{ $row->created_at->format('Y-m-d H:i:s') }}</span>
					</x-admin.td>
					<x-admin.td>
						<x-admin.badge :variant="$row->context === 'webhook' ? 'blue' : 'violet'">{{ $row->context }}</x-admin.badge>
					</x-admin.td>
					<x-admin.td class="font-mono text-sm">{{ $row->telegram_id ?? '—' }}</x-admin.td>
					<x-admin.td class="text-xs text-slate-600 max-w-[120px] truncate" title="{{ $row->path }}">{{ $row->path ?? '—' }}</x-admin.td>
					<x-admin.td class="max-w-[280px]">
						<div class="text-sm font-medium text-rose-800 truncate" title="{{ $row->exception_message }}">{{ $row->exception_message }}</div>
						@if($row->exception_class)
							<div class="text-xs text-slate-500">{{ $row->exception_class }}</div>
						@endif
					</x-admin.td>
					<x-admin.td align="right">
						<button type="button" wire:click="showDetail({{ $row->id }})"
							class="text-sm font-semibold text-slate-900 hover:text-slate-700 underline">
							Подробнее
						</button>
					</x-admin.td>
				</tr>
			@empty
				<tr>
					<td class="px-4 py-10 text-center text-slate-500" colspan="7">Записей нет</td>
				</tr>
			@endforelse
		</x-admin.table>

		<div class="pt-3">
			{{ $rows->links() }}
		</div>
	</x-admin.card>

	@if($errorDetail ?? null)
		<div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
			<div class="flex min-h-full items-center justify-center p-4">
				<div class="fixed inset-0 bg-slate-900/50" wire:click="closeDetail"></div>
				<div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
					<h3 class="text-lg font-semibold text-slate-900">Обращение #{{ $errorDetail->id }}</h3>
					<div class="mt-4 space-y-2 text-sm">
						<div><span class="font-semibold text-slate-600">Дата:</span> {{ $errorDetail->created_at->format('Y-m-d H:i:s') }}</div>
						<div><span class="font-semibold text-slate-600">Контекст:</span> {{ $errorDetail->context }}</div>
						<div><span class="font-semibold text-slate-600">Telegram ID:</span> {{ $errorDetail->telegram_id ?? '—' }}</div>
						<div><span class="font-semibold text-slate-600">Путь:</span> {{ $errorDetail->path ?? '—' }}</div>
						@if($errorDetail->request_summary)
							<div><span class="font-semibold text-slate-600">Данные запроса:</span><pre class="mt-1 rounded bg-slate-100 p-2 text-xs overflow-x-auto">@json($errorDetail->request_summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre></div>
						@endif
						<div><span class="font-semibold text-slate-600">Ошибка:</span> <span class="text-rose-700">{{ $errorDetail->exception_message }}</span></div>
						@if($errorDetail->exception_class)
							<div><span class="font-semibold text-slate-600">Класс:</span> {{ $errorDetail->exception_class }}</div>
						@endif
						@if($errorDetail->exception_trace)
							<div><span class="font-semibold text-slate-600">Трассировка:</span><pre class="mt-1 max-h-60 overflow-auto rounded bg-slate-100 p-2 text-xs whitespace-pre-wrap">{{ $errorDetail->exception_trace }}</pre></div>
						@endif
					</div>
					<div class="mt-6 flex justify-end">
						<button type="button" wire:click="closeDetail" class="rounded-xl px-4 py-2 text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800">
							Закрыть
						</button>
					</div>
				</div>
			</div>
		</div>
	@endif
</div>

<div class="space-y-6">
	<x-admin.page-header
		title="Delivery Orders"
		subtitle="Публичные заказы доставки: выдача аккаунта, QR/device-code подключение и контроль попыток."
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

	<x-admin.filters-bar>
		<div class="lg:col-span-4">
			<x-admin.filter-input
				label="Поиск"
				placeholder="заказ / email / игра / логин / код..."
				icon="search"
				wire:model.live.debounce.300ms="q"
			/>
		</div>

		<div class="lg:col-span-3">
			<x-admin.filter-select label="Статус" wire:model.live="statusFilter">
				<option value="">Все статусы</option>
				@foreach($statusOptions as $status)
					<option value="{{ $status }}">{{ $this->statusLabel($status) }}</option>
				@endforeach
			</x-admin.filter-select>
		</div>

		<div class="lg:col-span-3">
			<x-admin.filter-select label="Платформа" wire:model.live="platformFilter">
				<option value="">Все платформы</option>
				@foreach($platformOptions as $platform)
					<option value="{{ $platform }}">{{ $platform }}</option>
				@endforeach
			</x-admin.filter-select>
		</div>

		<div class="lg:col-span-2 flex items-end">
			<x-admin.button variant="secondary" size="sm" wire:click="clearFilters" class="w-full">
				Сброс
			</x-admin.button>
		</div>
	</x-admin.filters-bar>

	<x-admin.card>
		<x-admin.table-toolbar :density="($density ?? 'normal')" :showDensity="true" />

		<x-admin.table :density="($density ?? 'normal')" :zebra="true" :sticky="true">
			<x-slot:head>
				<tr>
					<x-admin.th sortable :sorted="$sortBy === 'id'" :direction="$sortBy === 'id' ? $sortDirection : null" sortField="id">ID</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'order_number'" :direction="$sortBy === 'order_number' ? $sortDirection : null" sortField="order_number">Заказ</x-admin.th>
					<x-admin.th>Клиент</x-admin.th>
					<x-admin.th>Игра / платформа</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'status'" :direction="$sortBy === 'status' ? $sortDirection : null" sortField="status">Статус</x-admin.th>
					<x-admin.th>Аккаунт</x-admin.th>
					<x-admin.th>Попытки</x-admin.th>
					<x-admin.th sortable :sorted="$sortBy === 'created_at'" :direction="$sortBy === 'created_at' ? $sortDirection : null" sortField="created_at">Создан</x-admin.th>
					<x-admin.th align="right">Действие</x-admin.th>
				</tr>
			</x-slot:head>

			@forelse($rows as $row)
				<tr>
					<x-admin.td class="font-semibold text-slate-900">#{{ $row->id }}</x-admin.td>

					<x-admin.td>
						<div class="font-semibold text-slate-900">{{ $row->order_number }}</div>
						<div class="text-xs text-slate-400">{{ str($row->token)->limit(12) }}</div>
					</x-admin.td>

					<x-admin.td>
						<div class="text-sm text-slate-700">{{ $row->customer_email }}</div>
						@if($row->operator)
							<div class="text-xs text-slate-400">оператор: {{ $row->operator->username ?: $row->operator->first_name }}</div>
						@endif
					</x-admin.td>

					<x-admin.td>
						<div class="font-semibold text-slate-900">{{ $row->game ?: '—' }}</div>
						<div class="flex flex-wrap gap-1 pt-1">
							<x-admin.badge variant="blue">{{ $row->platform }}</x-admin.badge>
							@if($row->issue_platform && $row->issue_platform !== $row->platform)
								<x-admin.badge variant="violet">{{ $row->issue_platform }}</x-admin.badge>
							@endif
						</div>
					</x-admin.td>

					<x-admin.td>
						<x-admin.badge :variant="$this->statusVariant($row->status->value)">
							{{ $this->statusLabel($row->status->value) }}
						</x-admin.badge>
						@if($row->connection_locked_until)
							<div class="text-xs text-slate-400 mt-1">до {{ $row->connection_locked_until->format('d.m.Y H:i') }}</div>
						@endif
					</x-admin.td>

					<x-admin.td>
						@if($row->display_login)
							<div class="font-semibold text-slate-900">{{ $row->display_login }}</div>
							<div class="text-xs text-slate-400">{{ $row->display_password_type?->value ?? '—' }}</div>
						@else
							<span class="text-slate-400">—</span>
						@endif
					</x-admin.td>

					<x-admin.td>
						<div class="text-sm font-semibold text-slate-900">
							{{ $row->connection_attempts_used }} / {{ $row->connection_attempts_limit }}
						</div>
						@if($row->last_connection_code)
							<div class="text-xs text-slate-400">код: {{ $row->last_connection_code }}</div>
						@endif
					</x-admin.td>

					<x-admin.td>
						<div class="text-sm text-slate-700">{{ $row->created_at?->format('d.m.Y H:i') }}</div>
						@if($row->token_expires_at)
							<div class="text-xs text-slate-400">истекает {{ $row->token_expires_at->format('d.m.Y H:i') }}</div>
						@endif
					</x-admin.td>

					<x-admin.td align="right">
						<x-admin.button variant="secondary" size="sm" href="{{ route('admin.delivery-orders.show', $row) }}">
							Открыть
						</x-admin.button>
					</x-admin.td>
				</tr>
			@empty
				<tr>
					<x-admin.td colspan="9" class="text-center py-10 text-slate-500">
						Delivery-заказы не найдены
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

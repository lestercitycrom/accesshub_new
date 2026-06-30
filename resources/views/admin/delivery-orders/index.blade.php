<div class="space-y-4">
	<div class="flex flex-wrap items-start justify-between gap-3">
		<div class="min-w-0">
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">Delivery Orders</h1>
			<p class="mt-1 text-sm text-slate-500">Публичные заказы доставки: выдача аккаунта, QR/device-code подключение и контроль попыток.</p>
		</div>

		<x-admin.button variant="secondary" size="sm" wire:click="$refresh">
			<span class="inline-flex items-center gap-2">
				<x-admin.icon name="refresh" class="h-4 w-4" />
				Обновить
			</span>
		</x-admin.button>
	</div>

	<div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
		<div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
			<div class="lg:col-span-5">
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

			<div class="lg:col-span-1 flex items-end">
				<x-admin.button variant="secondary" size="sm" wire:click="clearFilters" class="w-full">
					Сброс
				</x-admin.button>
			</div>
		</div>
	</div>

	<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
		<table class="w-full table-fixed text-[13px]">
			<thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
				<tr>
					<th class="w-[23%] px-3 py-2 text-left">Заказ / игра</th>
					<th class="w-[17%] px-3 py-2 text-left">Клиент</th>
					<th class="w-[12%] px-3 py-2 text-left">
						<button type="button" wire:click="sort('created_at')" class="inline-flex items-center gap-1 hover:text-slate-900">
							Создан
							@if($sortBy === 'created_at')
								<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
							@endif
						</button>
					</th>
					<th class="w-[17%] px-3 py-2 text-left">Аккаунт</th>
					<th class="w-[13%] px-3 py-2 text-left">Статус</th>
					<th class="w-[8%] px-3 py-2 text-left">Код</th>
					<th class="w-[10%] px-3 py-2 text-right"></th>
				</tr>
			</thead>
			<tbody class="divide-y divide-slate-100">
				@forelse($rows as $row)
					<tr class="align-middle odd:bg-white even:bg-slate-50/45 hover:bg-slate-100/70">
						<td class="px-3 py-2">
							<div class="flex min-w-0 items-center gap-2">
								<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-xs font-semibold text-white">
									{{ mb_strtoupper(mb_substr((string) ($row->game ?: $row->platform ?: 'D'), 0, 1)) }}
								</div>
								<div class="min-w-0">
									<div class="truncate font-semibold text-slate-900" title="{{ $row->game ?: '—' }}">{{ $row->game ?: '—' }}</div>
									<div class="flex min-w-0 items-center gap-2 text-[11px] text-slate-500">
										<span class="shrink-0">#{{ $row->order_number }}</span>
										<span class="truncate rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-sky-700">{{ $row->issue_platform ?: $row->platform }}</span>
									</div>
								</div>
							</div>
						</td>

						<td class="px-3 py-2">
							<div class="truncate font-medium text-slate-800" title="{{ $row->customer_email }}">{{ $row->customer_email }}</div>
							<div class="truncate text-[11px] text-slate-500">
								@if($row->operator)
									{{ $row->operator->username ?: $row->operator->first_name }}
								@else
									оператор не назначен
								@endif
							</div>
						</td>

						<td class="px-3 py-2 text-slate-600">
							<div class="leading-tight">{{ $row->created_at?->format('d.m.Y') }}</div>
							<div class="text-[11px] text-slate-500">{{ $row->created_at?->format('H:i') }}</div>
						</td>

						<td class="px-3 py-2">
							@if($row->display_login)
								<div class="truncate font-semibold text-slate-900" title="{{ $row->display_login }}">{{ $row->display_login }}</div>
								<div class="text-[11px] text-slate-500">{{ $row->display_password_type?->value ?? '-' }}</div>
							@else
								<div class="text-slate-400">—</div>
							@endif
						</td>

						<td class="px-3 py-2">
							<x-admin.badge :variant="$this->statusVariant($row->status->value)">
								{{ $this->statusLabel($row->status->value) }}
							</x-admin.badge>
							@if($row->connection_locked_until)
								<div class="mt-1 truncate text-[11px] text-slate-500">до {{ $row->connection_locked_until->format('d.m H:i') }}</div>
							@endif
						</td>

						<td class="px-3 py-2">
							<div class="font-semibold text-slate-900">{{ $row->connection_attempts_used }}/{{ $row->connection_attempts_limit }}</div>
							@if($row->last_connection_code)
								<div class="truncate text-[11px] text-slate-500">{{ $row->last_connection_code }}</div>
							@endif
						</td>

						<td class="px-3 py-2 text-right">
							<x-admin.button variant="secondary" size="sm" href="{{ route('admin.delivery-orders.show', $row) }}">
								Открыть
							</x-admin.button>
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="7" class="px-3 py-10 text-center text-slate-500">
							Delivery-заказы не найдены
						</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>

	@if(method_exists($rows, 'links'))
		<div>
			{{ $rows->links() }}
		</div>
	@endif
</div>

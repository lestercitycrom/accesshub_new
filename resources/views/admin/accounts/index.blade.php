<div class="space-y-6">
	@php
		$activeFilters = 0;
		$activeFilters += !empty($q) ? 1 : 0;
		$activeFilters += !empty($gameFilter) ? 1 : 0;
		$activeFilters += !empty($platformFilter) ? 1 : 0;
		$activeFilters += !empty($statusFilter) ? 1 : 0;
	@endphp

	<x-admin.page-header
		title="Аккаунты"
		subtitle="Поиск, фильтры, быстрый доступ к карточке и экспорт."
	>
		<x-admin.page-actions primaryLabel="Создать" primaryIcon="database" :primaryHref="route('admin.accounts.create')">
			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.account-lookup') }}">
				Поиск
			</a>

			@if(isset($exportUrl))
				<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
					href="{{ $exportUrl }}">
					Экспорт CSV
				</a>
			@endif

			<form method="POST" action="{{ route('admin.accounts.import') }}" enctype="multipart/form-data" class="inline-flex items-center gap-2">
				@csrf
				<input id="accountsImportFile" name="file" type="file" accept=".csv,.txt" class="hidden"
					onchange="this.form.submit()">

				<label for="accountsImportFile" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer">
					Import CSV
				</label>
			</form>
		</x-admin.page-actions>

		<x-slot:breadcrumbs>
			<span class="text-slate-500">Админ</span>
			<span class="px-1 text-slate-300">/</span>
			<span class="font-semibold text-slate-700">Аккаунты</span>
		</x-slot:breadcrumbs>
	</x-admin.page-header>

	@if(session('status'))
		<x-admin.alert variant="success" :message="session('status')" />
	@elseif($alertMessage ?? null)
		<x-admin.alert variant="success" :message="$alertMessage" />
	@endif

	{{-- Cooldown widget --}}
	@php $cooldownCount = $cooldownAccounts->count(); @endphp
	<x-admin.card>
		<x-slot:actions>
			@if($cooldownCount > 0)
				<span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
					{{ $cooldownCount }} шт.
				</span>
			@endif
		</x-slot:actions>
		<x-slot:title>Аккаунты на кулдауне</x-slot:title>

		@if($cooldownCount === 0)
			<p class="text-sm text-slate-500">Нет аккаунтов на кулдауне — все доступны для выдачи.</p>
		@else
			<x-admin.table density="compact" :zebra="true">
				<x-slot:head>
					<tr>
						<x-admin.th>ID</x-admin.th>
						<x-admin.th>Игра</x-admin.th>
						<x-admin.th>Платформа</x-admin.th>
						<x-admin.th>Логин</x-admin.th>
						<x-admin.th>Вернётся в пул</x-admin.th>
						<x-admin.th>Осталось</x-admin.th>
					</tr>
				</x-slot:head>
				@foreach($cooldownAccounts as $ca)
					@php $daysLeft = (int) now()->diffInDays($ca->next_release_at, false); @endphp
					<tr class="hover:bg-slate-50/70">
						<x-admin.td>
							<a href="{{ route('admin.accounts.show', $ca) }}" class="font-semibold text-blue-600 hover:underline">#{{ $ca->id }}</a>
						</x-admin.td>
						<x-admin.td>{{ $ca->game }}</x-admin.td>
						<x-admin.td>
							@if(is_array($ca->platform))
								<div class="flex flex-wrap gap-1">
									@foreach($ca->platform as $p)
										<x-admin.badge variant="blue" class="text-xs">{{ $p }}</x-admin.badge>
									@endforeach
								</div>
							@else
								{{ $ca->platform }}
							@endif
						</x-admin.td>
						<x-admin.td class="text-slate-700">{{ $ca->login }}</x-admin.td>
						<x-admin.td>
							<x-admin.badge variant="amber">{{ $ca->next_release_at->format('d.m.Y H:i') }}</x-admin.badge>
						</x-admin.td>
						<x-admin.td class="text-slate-500 text-xs">
							{{ $daysLeft > 0 ? $daysLeft.' дн.' : 'менее дня' }}
						</x-admin.td>
					</tr>
				@endforeach
			</x-admin.table>
		@endif
	</x-admin.card>

	<x-admin.filters-bar>
		<div class="lg:col-span-3">
			<x-admin.filter-input
				label="Поиск"
				placeholder="игра / логин / почта / ID..."
				icon="search"
				wire:model.live="q"
			/>
		</div>

		<div class="lg:col-span-2">
			<x-admin.filter-select label="Игра" icon="list" wire:model.live="gameFilter">
				<option value="">Любая</option>
				@foreach($gameOptions as $g)
					<option value="{{ $g }}">{{ $g }}</option>
				@endforeach
			</x-admin.filter-select>
		</div>

		<div class="lg:col-span-2">
			<x-admin.filter-select label="Платформа" icon="list" wire:model.live="platformFilter">
				<option value="">Любая</option>
				@foreach($platformOptions as $p)
					<option value="{{ $p }}">{{ $p }}</option>
				@endforeach
			</x-admin.filter-select>
		</div>

		<div class="lg:col-span-2">
			<x-admin.filter-select label="Статус" icon="list" wire:model.live="statusFilter">
				<option value="">Любой</option>
				@foreach($statusOptions as $s)
					@php $sLabels=['ACTIVE'=>'Активен','RECOVERY'=>'Восстановление','STOLEN'=>'Украден','TEMP_HOLD'=>'На паузе','DEAD'=>'Мёртвый','COOLDOWN'=>'Кулдаун']; @endphp
					<option value="{{ $s }}">{{ $sLabels[$s] ?? $s }}</option>
				@endforeach
			</x-admin.filter-select>
		</div>

		<div class="lg:col-span-3 flex items-end gap-2">
			<x-admin.button variant="secondary" size="sm" wire:click="clearFilters">Сброс</x-admin.button>
		</div>
	</x-admin.filters-bar>

	<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-3 py-2 text-[11px] text-slate-500">
			Назначен — Telegram ID оператора (обычно при «Украден»). Дедлайн — срок статуса «Украден» (продлевается «Перенести на 1 день»).
		</div>

		<table class="w-full table-fixed text-[13px]">
			<thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
				<tr>
					<th class="w-[6%] px-3 py-2 text-left">
						<button type="button" wire:click="sort('id')" class="inline-flex items-center gap-1 hover:text-slate-900">ID @if($sortBy==='id')<span>{{ $sortDirection==='asc'?'↑':'↓' }}</span>@endif</button>
					</th>
					<th class="w-[29%] px-3 py-2 text-left">
						<button type="button" wire:click="sort('game')" class="inline-flex items-center gap-1 hover:text-slate-900">Игра / платформа @if($sortBy==='game')<span>{{ $sortDirection==='asc'?'↑':'↓' }}</span>@endif</button>
					</th>
					<th class="w-[24%] px-3 py-2 text-left">
						<button type="button" wire:click="sort('login')" class="inline-flex items-center gap-1 hover:text-slate-900">Логин @if($sortBy==='login')<span>{{ $sortDirection==='asc'?'↑':'↓' }}</span>@endif</button>
					</th>
					<th class="w-[13%] px-3 py-2 text-left">
						<button type="button" wire:click="sort('status')" class="inline-flex items-center gap-1 hover:text-slate-900">Статус @if($sortBy==='status')<span>{{ $sortDirection==='asc'?'↑':'↓' }}</span>@endif</button>
					</th>
					<th class="w-[12%] px-3 py-2 text-left">Назначен</th>
					<th class="w-[10%] px-3 py-2 text-left">
						<button type="button" wire:click="sort('status_deadline_at')" class="inline-flex items-center gap-1 hover:text-slate-900">Дедлайн @if($sortBy==='status_deadline_at')<span>{{ $sortDirection==='asc'?'↑':'↓' }}</span>@endif</button>
					</th>
					<th class="w-[6%] px-3 py-2 text-right"></th>
				</tr>
			</thead>
			<tbody class="divide-y divide-slate-100">
				@forelse($rows as $row)
					@php $isOnCooldown = $row->next_release_at && $row->next_release_at->isFuture(); @endphp
					<tr class="align-middle odd:bg-white even:bg-slate-50/45 hover:bg-slate-100/70">
						<td class="px-3 py-2 font-semibold text-slate-900">{{ $row->id }}</td>

						<td class="px-3 py-2">
							<div class="flex min-w-0 items-center gap-2">
								<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-[11px] font-semibold text-white">
									{{ mb_strtoupper(mb_substr((string) ($row->game ?: 'A'), 0, 1)) }}
								</div>
								<div class="min-w-0">
									<div class="truncate font-semibold text-slate-900" title="{{ $row->game }}">{{ $row->game ?: '—' }}</div>
									<div class="mt-0.5 flex flex-wrap gap-1">
										@foreach((is_array($row->platform) ? $row->platform : [$row->platform]) as $p)
											@if($p)<span class="rounded-full border border-sky-200 bg-sky-50 px-1.5 py-0 text-[10px] text-sky-700">{{ $p }}</span>@endif
										@endforeach
									</div>
								</div>
							</div>
						</td>

						<td class="px-3 py-2">
							<div class="truncate font-semibold text-slate-900" title="{{ $row->login }}">{{ $row->login }}</div>
							@php $mail = $row->mail_account_login ?: (is_array($row->meta) ? ($row->meta['email_login'] ?? null) : null); @endphp
							@if($mail)
								<div class="truncate text-[11px] text-slate-500" title="{{ $mail }}">{{ $mail }}</div>
							@endif
						</td>

						<td class="px-3 py-2">
							<x-admin.status-badge :status="$isOnCooldown ? 'COOLDOWN' : $row->status->value" />
							@if($isOnCooldown)
								<div class="mt-0.5 text-[11px] text-slate-500">до {{ $row->next_release_at->format('d.m.Y') }}</div>
							@endif
						</td>

						<td class="px-3 py-2">
							@if($row->assignedOperator)
								<span class="truncate text-slate-700" title="{{ $row->assignedOperator->username ?: $row->assignedOperator->first_name }}">{{ $row->assignedOperator->username ?: $row->assignedOperator->first_name }}</span>
							@elseif($row->assigned_to_telegram_id)
								<span class="text-slate-700">{{ $row->assigned_to_telegram_id }}</span>
							@else
								<span class="text-slate-400">—</span>
							@endif
						</td>

						<td class="px-3 py-2 text-slate-600">
							@if($row->status_deadline_at)
								<div class="leading-tight">{{ $row->status_deadline_at->format('d.m.Y') }}</div>
								<div class="text-[11px] text-slate-500">{{ $row->status_deadline_at->format('H:i') }}</div>
							@else
								<span class="text-slate-400">—</span>
							@endif
						</td>

						<td class="px-3 py-2">
							<div class="flex items-center justify-end gap-1">
								<x-admin.icon-button icon="eye" title="Открыть" :href="route('admin.accounts.show', $row)" />
								<x-admin.icon-button icon="pencil" title="Редактировать" :href="route('admin.accounts.edit', $row)" />
								<x-admin.icon-button
									icon="trash"
									title="Удалить"
									variant="danger"
									wire:click="deleteAccount({{ $row->id }})"
									onclick="if(!confirm('Удалить аккаунт #{{ $row->id }}?')){event.preventDefault();event.stopImmediatePropagation();}"
								/>
							</div>
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="7" class="px-3 py-10 text-center text-slate-500">Аккаунты не найдены</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>

	@if(method_exists($rows, 'links'))
		<div class="pt-1">
			{{ $rows->links() }}
		</div>
	@endif

</div>

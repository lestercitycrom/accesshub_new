<div class="space-y-6">

	<x-admin.page-header
		title="Аккаунт #{{ $account->id }}"
		subtitle="{{ $account->game }} / {{ is_array($account->platform) ? implode(', ', $account->platform) : $account->platform }} · {{ $account->login }}"
	>
		<x-admin.page-actions primaryLabel="Редактировать" :primaryHref="route('admin.accounts.edit', $account)">
			<x-admin.button variant="secondary" href="{{ route('admin.account-lookup') }}">Поиск</x-admin.button>
			<x-admin.button variant="secondary" href="{{ route('admin.accounts.index') }}">К списку</x-admin.button>
		</x-admin.page-actions>

		<x-slot:breadcrumbs>
			<span class="text-slate-500">Аккаунты</span>
			<span class="px-1 text-slate-300">/</span>
			<span class="font-semibold text-slate-700">#{{ $account->id }}</span>
		</x-slot:breadcrumbs>
	</x-admin.page-header>

	@if(session('message'))
		<x-admin.alert variant="success" :message="session('message')" />
	@endif

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

		{{-- Сводка --}}
		<x-admin.card title="Сводка">
			<div class="space-y-4">

				{{-- Статус + бейджи --}}
				<div class="flex flex-wrap items-center gap-2">
					<x-admin.status-badge :status="($account->next_release_at && $account->next_release_at->isFuture()) ? 'COOLDOWN' : $account->status->value" />

					@if($account->next_release_at && $account->next_release_at->isFuture())
						<x-admin.badge variant="amber">
							Кулдаун · вернётся {{ $account->next_release_at->format('d.m.Y H:i') }}
						</x-admin.badge>
					@endif

					@if($account->assignedOperator)
						<x-admin.badge variant="violet">{{ $account->assignedOperator->username ?: $account->assignedOperator->first_name }}</x-admin.badge>
					@endif

					@if($account->status_deadline_at)
						<x-admin.badge variant="amber">до {{ $account->status_deadline_at->format('d.m.Y H:i') }}</x-admin.badge>
					@endif
				</div>

				{{-- Блок кулдауна --}}
				@if($account->next_release_at && $account->next_release_at->isFuture())
					@php
						$lastIssuance = $issuances->first();
						$daysLeft = (int) now()->diffInDays($account->next_release_at, false);
					@endphp
					<div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 space-y-1.5">
						<div class="text-xs font-semibold text-amber-700">Аккаунт на кулдауне</div>
						<div class="grid grid-cols-2 gap-2 text-xs">
							<div>
								<div class="text-amber-600/70">Вернётся в пул</div>
								<div class="font-semibold text-amber-900">{{ $account->next_release_at->format('d.m.Y H:i') }}</div>
							</div>
							<div>
								<div class="text-amber-600/70">Осталось</div>
								<div class="font-semibold text-amber-900">{{ $daysLeft > 0 ? $daysLeft.' дн.' : 'менее дня' }}</div>
							</div>
							@if($lastIssuance)
								<div>
									<div class="text-amber-600/70">Последняя выдача</div>
									<div class="font-semibold text-amber-900">{{ $lastIssuance->issued_at?->format('d.m.Y H:i') }}</div>
								</div>
								<div>
									<div class="text-amber-600/70">Кому выдан</div>
									<div class="font-semibold text-amber-900">{{ $lastIssuance->telegramUser?->username ?? $lastIssuance->telegram_id }}</div>
								</div>
							@endif
						</div>
					</div>
				@endif

				{{-- Поля --}}
				<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
					<x-admin.field label="Игра">{{ $account->game }}</x-admin.field>

					<x-admin.field label="Платформа">
						@if(is_array($account->platform))
							<div class="flex flex-wrap gap-1">
								@foreach($account->platform as $p)
									<x-admin.badge variant="blue">{{ $p }}</x-admin.badge>
								@endforeach
							</div>
						@else
							{{ $account->platform }}
						@endif
					</x-admin.field>

					<x-admin.field label="Логин">{{ $account->login }}</x-admin.field>

					<x-admin.field label="Пароль">{{ $account->password ?? '—' }}</x-admin.field>

					<x-admin.field label="Лимит выдач">{{ $account->max_uses }}</x-admin.field>

					<x-admin.field label="Доступно выдач">{{ $account->available_uses }}</x-admin.field>

					@if($account->assigned_to_telegram_id)
						<x-admin.field label="Назначен оператор" class="sm:col-span-2">
							@if($account->assignedOperator)
								{{ $account->assignedOperator->username ?: $account->assignedOperator->first_name }}
								<span class="text-slate-400 font-normal">(ID: {{ $account->assigned_to_telegram_id }})</span>
							@else
								{{ $account->assigned_to_telegram_id }}
							@endif
						</x-admin.field>
					@endif

					@if($account->status_deadline_at)
						<x-admin.field label="Дедлайн статуса" class="sm:col-span-2">
							{{ $account->status_deadline_at->format('d.m.Y H:i') }}
						</x-admin.field>
					@endif
				</div>

				{{-- Флаги --}}
				@if(is_array($account->flags) && count(array_filter($account->flags)) > 0)
					<div class="space-y-1.5">
						<div class="text-xs font-medium text-slate-400">Флаги</div>
						<div class="flex flex-wrap gap-2">
							@foreach($account->flags as $k => $v)
								@if($v)
									<x-admin.badge variant="red">{{ $k }}</x-admin.badge>
								@endif
							@endforeach
						</div>
					</div>
				@endif

				{{-- Дополнительно --}}
				@if($account->mail_account_login || $account->mail_account_password || $account->comment || $account->two_fa_mail_account_date || $account->recover_code)
					<div class="space-y-2 pt-3 border-t border-slate-100">
						<div class="text-xs font-medium text-slate-400">Дополнительно</div>

						<div class="grid grid-cols-1 gap-2">
							@if($account->mail_account_login)
								<x-admin.field label="Логин почты">{{ $account->mail_account_login }}</x-admin.field>
							@endif
							@if($account->mail_account_password)
								<x-admin.field label="Пароль почты">{{ $account->mail_account_password }}</x-admin.field>
							@endif
							@if($account->two_fa_mail_account_date)
								<x-admin.field label="Дата 2FA почты">{{ $account->two_fa_mail_account_date }}</x-admin.field>
							@endif
							@if($account->recover_code)
								<x-admin.field label="Код восстановления">
									<span class="whitespace-pre-wrap">{{ $account->recover_code }}</span>
								</x-admin.field>
							@endif
							@if($account->comment)
								<x-admin.field label="Комментарий">
									<span class="whitespace-pre-wrap font-normal text-slate-700">{{ $account->comment }}</span>
								</x-admin.field>
							@endif
						</div>
					</div>
				@endif

			</div>
		</x-admin.card>

		{{-- Действия --}}
		<x-admin.card title="Действия">
			<div class="space-y-4">

				<div class="space-y-3">
					<div class="space-y-1">
						<label class="text-xs font-medium text-slate-500">Изменить статус</label>
						<select wire:model="setStatus"
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
							@php $sL=['ACTIVE'=>'Активен','RECOVERY'=>'Восстановление','STOLEN'=>'Украден','TEMP_HOLD'=>'На паузе','DEAD'=>'Мёртвый']; @endphp
							@foreach($statuses as $s)
								<option value="{{ $s }}">{{ $sL[$s] ?? $s }}</option>
							@endforeach
						</select>
					</div>

					@if($setStatus === 'STOLEN')
						<div class="space-y-1">
							<label class="text-xs font-medium text-slate-500">Назначить оператора</label>
							<select wire:model="assignToTelegramId"
								class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
								<option value="">— не назначать</option>
								@foreach($operators ?? [] as $op)
									<option value="{{ $op->telegram_id }}">{{ $op->username ?: $op->first_name }} ({{ $op->telegram_id }})</option>
								@endforeach
							</select>
						</div>
					@endif

					<x-admin.button variant="primary" size="md" wire:click="applyStatus" class="w-full">
						Применить статус
					</x-admin.button>

					<x-admin.button variant="secondary" size="md" wire:click="releaseToPool" class="w-full">
						Вернуть в пул
					</x-admin.button>
				</div>

				<div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
					<div class="text-sm font-semibold text-slate-700">Смена пароля</div>

					<x-admin.input
						label="Новый пароль"
						type="text"
						placeholder="введите новый пароль..."
						wire:model="newPassword"
					/>

					<x-admin.button variant="danger" size="md" wire:click="updatePassword" class="w-full">
						Обновить пароль
					</x-admin.button>

					<p class="text-xs text-slate-400">
						При смене пароля флаги «Требуется обновление пароля» и «Требуются действия» сбрасываются, статус возвращается в «Активен».
					</p>
				</div>

			</div>
		</x-admin.card>

		{{-- Заметки --}}
		<x-admin.card title="Заметки">
			<div class="space-y-2 text-sm text-slate-600">
				<p>Проблемные статусы используются для массовых действий и отметок проблемных аккаунтов.</p>
				<ul class="list-disc pl-5 space-y-1">
					<li>«Украден» — аккаунт украден + блокировка выдачи</li>
					<li>«Восстановление» — аккаунт в процессе восстановления</li>
					<li>«На паузе» и «Мёртвый» — ручные пометки и блокировка</li>
				</ul>
			</div>
		</x-admin.card>

	</div>

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

		{{-- Выдачи --}}
		<x-admin.card title="Выдачи (последние 20)">
			<div class="overflow-x-auto rounded-xl border border-slate-200">
				<table class="min-w-full text-sm">
					<thead class="bg-slate-50 border-b border-slate-200">
						<tr>
							<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Дата</th>
							<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Заказ</th>
							<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Пользователь</th>
							<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Кол-во</th>
							<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Cooldown</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100 bg-white">
						@forelse($issuances as $i)
							<tr class="hover:bg-slate-50/70">
								<td class="px-4 py-3 text-sm text-slate-600">{{ $i->issued_at?->format('Y-m-d H:i') }}</td>
								<td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $i->order_id }}</td>
								<td class="px-4 py-3">
									<div class="text-sm font-medium text-slate-900">{{ $i->telegramUser?->username ?? '—' }}</div>
									<div class="text-xs text-slate-400">{{ $i->telegram_id }}</div>
								</td>
								<td class="px-4 py-3 text-sm text-slate-700">{{ $i->qty }}</td>
								<td class="px-4 py-3 text-sm text-slate-600">{{ $i->cooldown_until?->format('Y-m-d') ?? '—' }}</td>
							</tr>
						@empty
							<tr>
								<td class="px-4 py-10 text-center text-sm text-slate-400" colspan="5">Выдач нет</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</x-admin.card>

		{{-- События --}}
		<x-admin.card title="События аккаунта (последние 50)">
			<div class="overflow-x-auto rounded-xl border border-slate-200">
				<table class="min-w-full text-sm">
					<thead class="bg-slate-50 border-b border-slate-200">
						<tr>
							<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Дата</th>
							<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Тип</th>
							<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Telegram ID</th>
							<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Данные</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100 bg-white">
						@forelse($events as $e)
							<tr class="hover:bg-slate-50/70">
								<td class="px-4 py-3 text-sm text-slate-600">{{ $e->created_at?->format('Y-m-d H:i') }}</td>
								<td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $e->type }}</td>
								<td class="px-4 py-3 text-sm text-slate-600">{{ $e->telegram_id ?? '—' }}</td>
								<td class="px-4 py-3">
									<pre class="text-xs text-slate-500 whitespace-pre-wrap">@json($e->payload)</pre>
								</td>
							</tr>
						@empty
							<tr>
								<td class="px-4 py-10 text-center text-sm text-slate-400" colspan="4">Событий нет</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</x-admin.card>

	</div>

</div>

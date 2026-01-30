<div class="space-y-6">
	@if(session('message'))
		<div class="rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
			{{ session('message') }}
		</div>
	@endif

	<div class="flex flex-wrap items-start justify-between gap-3">
		<div>
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">
				Аккаунт #{{ $account->id }}
			</h1>

			<p class="text-sm text-slate-500">
				{{ $account->game }} / {{ is_array($account->platform) ? implode(', ', $account->platform) : $account->platform }} · <span class="font-semibold text-slate-900">{{ $account->login }}</span>
			</p>
		</div>

		<div class="flex flex-wrap items-center gap-2">
			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.accounts.edit', $account) }}">
				Редактировать
			</a>

			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.account-lookup') }}">
				Поиск
			</a>

			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.accounts.index') }}">
				К списку
			</a>
		</div>
	</div>

	@php
		$st = $account->status->value;
		$badge = match($st) {
			'ACTIVE' => 'green',
			'RECOVERY' => 'amber',
			'STOLEN' => 'red',
			'DEAD' => 'red',
			'TEMP_HOLD' => 'blue',
			default => 'gray',
		};
	@endphp

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
		<!-- Summary -->
		<x-admin.card title="Сводка">
			<div class="space-y-4">
				<div class="flex flex-wrap items-center gap-2">
					<x-admin.badge :variant="$badge">{{ $st }}</x-admin.badge>

					@if($account->assignedOperator)
						<x-admin.badge variant="violet">Назначен: {{ $account->assignedOperator->username ?: $account->assignedOperator->first_name }}</x-admin.badge>
					@elseif($account->assigned_to_telegram_id)
						<x-admin.badge variant="violet">Назначен: {{ $account->assigned_to_telegram_id }}</x-admin.badge>
					@else
						<x-admin.badge variant="gray">Назначен: —</x-admin.badge>
					@endif

					@if($account->status_deadline_at)
						<x-admin.badge variant="amber">
							Дедлайн: {{ $account->status_deadline_at->format('Y-m-d H:i') }}
						</x-admin.badge>
					@else
						<x-admin.badge variant="gray">Дедлайн: —</x-admin.badge>
					@endif
				</div>
				<div class="text-xs text-slate-500">
					Назначен — Telegram ID оператора, к которому закреплён аккаунт (обычно при статусе STOLEN).
					Дедлайн — срок статуса STOLEN; продлевается кнопкой «Перенести на 1 день».
				</div>

				<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
					<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Игра</div>
						<div class="font-semibold text-slate-900">{{ $account->game }}</div>
					</div>

					<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Платформа</div>
						<div class="font-semibold text-slate-900">
							@if(is_array($account->platform))
								@foreach($account->platform as $p)
									<x-admin.badge variant="blue">{{ $p }}</x-admin.badge>
								@endforeach
							@else
								{{ $account->platform }}
							@endif
						</div>
					</div>

					<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Логин</div>
						<div class="font-semibold text-slate-900 break-all">{{ $account->login }}</div>
					</div>

					<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Пароль</div>
						<div class="font-semibold text-slate-900 break-all">
							{{ $account->password ?? '—' }}
						</div>
					</div>
				</div>

				@if(is_array($account->flags) && count($account->flags) > 0)
					<div class="space-y-2">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Флаги</div>
						<div class="flex flex-wrap gap-2">
							@foreach($account->flags as $k => $v)
								@if($v)
									<x-admin.badge variant="blue">{{ $k }}</x-admin.badge>
								@endif
							@endforeach
						</div>
					</div>
				@endif

				@if($account->mail_account_login || $account->mail_account_password || $account->comment || $account->two_fa_mail_account_date || $account->recover_code)
					<div class="space-y-3 pt-4 border-t border-slate-200">
						<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Дополнительная информация</div>
						
						@if($account->mail_account_login)
							<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
								<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Логин почты</div>
								<div class="font-semibold text-slate-900 break-all">{{ $account->mail_account_login }}</div>
							</div>
						@endif

						@if($account->mail_account_password)
							<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
								<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Пароль почты</div>
								<div class="font-semibold text-slate-900 break-all">{{ $account->mail_account_password }}</div>
							</div>
						@endif

						@if($account->comment)
							<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
								<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Comment</div>
								<div class="font-semibold text-slate-900 break-all whitespace-pre-wrap">{{ $account->comment }}</div>
							</div>
						@endif

						@if($account->two_fa_mail_account_date)
							<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
								<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Почта двухфакторной защиты</div>
								<div class="font-semibold text-slate-900 break-all">{{ $account->two_fa_mail_account_date }}</div>
							</div>
						@endif

						@if($account->recover_code)
							<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
								<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Код восстановления</div>
								<div class="font-semibold text-slate-900 break-all whitespace-pre-wrap">{{ $account->recover_code }}</div>
							</div>
						@endif
					</div>
				@endif
			</div>
		</x-admin.card>

		<!-- Actions -->
		<x-admin.card title="Действия">
			<div class="space-y-4">
				<div class="grid grid-cols-1 gap-3">
					<div class="space-y-1">
						<label class="text-xs font-semibold text-slate-700">Изменить статус аккаунта</label>
						<select wire:model="setStatus"
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
							@foreach($statuses as $s)
								<option value="{{ $s }}">{{ $s }}</option>
							@endforeach
						</select>
					</div>

					@if($setStatus === 'STOLEN')
						<div class="space-y-1">
							<label class="text-xs font-semibold text-slate-700">Назначить оператора</label>
							<select wire:model="assignToTelegramId"
								class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
								<option value="">— не назначать</option>
								@foreach($operators ?? [] as $op)
									<option value="{{ $op->telegram_id }}">{{ $op->username ?: $op->first_name }} ({{ $op->telegram_id }})</option>
								@endforeach
							</select>
						</div>
					@endif

					<x-admin.button variant="primary" size="md" wire:click="applyStatus">
						Применить статус
					</x-admin.button>

					<x-admin.button variant="secondary" size="md" wire:click="releaseToPool">
						Вернуть в пул
					</x-admin.button>
				</div>

				<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-3">
					<div class="text-sm font-semibold text-slate-900">Смена пароля (вручную)</div>

					<x-admin.input
						label="Новый пароль"
						type="text"
						placeholder="введите новый пароль..."
						wire:model="newPassword"
					/>

					<x-admin.button variant="danger" size="md" wire:click="updatePassword">
						Обновить пароль
					</x-admin.button>

					<p class="text-xs text-slate-500">
						Статусы «Требуется обновление пароля» и «Требуются действия» выставляются при смене пароля и возвращаются в ACTIVE.
					</p>
				</div>
			</div>
		</x-admin.card>

		<!-- Notes -->
		<x-admin.card title="Заметки">
			<div class="space-y-2 text-sm text-slate-600">
				<p>Проблемные статусы используются для массовых действий и отметок проблемных аккаунтов.</p>
				<ul class="list-disc pl-5 space-y-1">
					<li>STOLEN — аккаунт украден + блокировка выдачи</li>
					<li>RECOVERY — аккаунт в процессе восстановления</li>
					<li>TEMP_HOLD и DEAD — используйте для ручных пометок и блокировки</li>
				</ul>
			</div>
		</x-admin.card>
	</div>

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
		<!-- Issuances -->
		<x-admin.card title="Выдачи (последние 20)">
			<div class="overflow-x-auto rounded-2xl border border-slate-200">
				<table class="min-w-full text-sm">
					<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
						<tr>
							<th class="px-4 py-3 text-left">Дата</th>
							<th class="px-4 py-3 text-left">Заказ</th>
							<th class="px-4 py-3 text-left">Пользователь</th>
							<th class="px-4 py-3 text-left">Кол-во</th>
							<th class="px-4 py-3 text-left">Cooldown</th>
						</tr>
					</thead>

					<tbody class="divide-y divide-slate-200 bg-white">
						@forelse($issuances as $i)
							<tr class="hover:bg-slate-50/70">
								<td class="px-4 py-3">
									<span class="font-medium text-slate-900">{{ $i->issued_at?->format('Y-m-d H:i') }}</span>
								</td>
								<td class="px-4 py-3 font-semibold text-slate-900">{{ $i->order_id }}</td>
								<td class="px-4 py-3">
									<div class="text-xs text-slate-500">{{ $i->telegram_id }}</div>
									<div class="font-semibold text-slate-900">{{ $i->telegramUser?->username ?? '-' }}</div>
								</td>
								<td class="px-4 py-3">{{ $i->qty }}</td>
								<td class="px-4 py-3">{{ $i->cooldown_until?->format('Y-m-d') ?? '—' }}</td>
							</tr>
						@empty
							<tr>
								<td class="px-4 py-10 text-center text-slate-500" colspan="5">
									Выдач нет
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</x-admin.card>

		<!-- Events -->
		<x-admin.card title="События аккаунта (последние 50)">
			<div class="overflow-x-auto rounded-2xl border border-slate-200">
				<table class="min-w-full text-sm">
					<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
						<tr>
							<th class="px-4 py-3 text-left">Дата</th>
							<th class="px-4 py-3 text-left">Тип</th>
							<th class="px-4 py-3 text-left">Telegram ID</th>
							<th class="px-4 py-3 text-left">Данные</th>
						</tr>
					</thead>

					<tbody class="divide-y divide-slate-200 bg-white">
						@forelse($events as $e)
							<tr class="hover:bg-slate-50/70">
								<td class="px-4 py-3">
									<span class="font-medium text-slate-900">{{ $e->created_at?->format('Y-m-d H:i') }}</span>
								</td>
								<td class="px-4 py-3 font-semibold text-slate-900">{{ $e->type }}</td>
								<td class="px-4 py-3">{{ $e->telegram_id ?? '—' }}</td>
								<td class="px-4 py-3">
									<pre class="text-xs whitespace-pre-wrap text-slate-700">@json($e->payload)</pre>
								</td>
							</tr>
						@empty
							<tr>
								<td class="px-4 py-10 text-center text-slate-500" colspan="4">
									Событий нет
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</x-admin.card>
	</div>
</div>

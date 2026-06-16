<div class="space-y-6">
	<x-admin.page-header
		title="Delivery #{{ $order->order_number }}"
		subtitle="{{ $order->customer_email }} · {{ $order->platform }}{{ $order->game ? ' · '.$order->game : '' }}"
	>
		<x-admin.page-actions>
			<x-admin.button variant="secondary" href="{{ route('admin.delivery-orders.index') }}">К списку</x-admin.button>
			<x-admin.button variant="secondary" href="{{ route('delivery.order.show', ['token' => $order->token]) }}" target="_blank">
				Публичная страница
			</x-admin.button>
		</x-admin.page-actions>

		<x-slot:breadcrumbs>
			<span class="text-slate-500">Delivery</span>
			<span class="px-1 text-slate-300">/</span>
			<span class="font-semibold text-slate-700">#{{ $order->id }}</span>
		</x-slot:breadcrumbs>
	</x-admin.page-header>

	@if(session('message'))
		<x-admin.alert variant="success" :message="session('message')" />
	@endif

	@if(session('error'))
		<x-admin.alert variant="danger" :message="session('error')" />
	@endif

	<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
		<x-admin.card title="Заказ">
			<div class="space-y-4">
				<div class="flex flex-wrap items-center gap-2">
					<x-admin.badge :variant="$this->statusVariant($order->status->value)">
						{{ $this->statusLabel($order->status->value) }}
					</x-admin.badge>

					@if($order->display_password_type)
						<x-admin.badge variant="{{ $order->display_password_type->value === 'fake' ? 'amber' : 'green' }}">
							{{ $order->display_password_type->value }}
						</x-admin.badge>
					@endif
				</div>

				<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
					<x-admin.field label="Order number">{{ $order->order_number }}</x-admin.field>
					<x-admin.field label="Email">{{ $order->customer_email }}</x-admin.field>
					<x-admin.field label="Platform">{{ $order->platform }}</x-admin.field>
					<x-admin.field label="Issue platform">{{ $order->issue_platform ?: '—' }}</x-admin.field>
					<x-admin.field label="Game">{{ $order->game ?: '—' }}</x-admin.field>
					<x-admin.field label="Operator">
						@if($order->operator)
							{{ $order->operator->username ?: $order->operator->first_name }} ({{ $order->operator_telegram_id }})
						@else
							{{ $order->operator_telegram_id ?: '—' }}
						@endif
					</x-admin.field>
					<x-admin.field label="Created">{{ $order->created_at?->format('d.m.Y H:i') }}</x-admin.field>
					<x-admin.field label="Expires">{{ $order->token_expires_at?->format('d.m.Y H:i') }}</x-admin.field>
				</div>

				@if($order->display_login)
					<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
						<div class="text-xs font-semibold text-slate-500 mb-3">Данные, показанные клиенту</div>
						<div class="grid grid-cols-1 gap-2">
							<x-admin.field label="Login">{{ $order->display_login }}</x-admin.field>
							<x-admin.field label="Password">{{ $order->display_password }}</x-admin.field>
						</div>
					</div>
				@endif

				@if($order->account)
					<div class="rounded-xl border border-slate-200 bg-white p-4">
						<div class="text-xs font-semibold text-slate-500 mb-3">Аккаунт в базе</div>
						<div class="space-y-2 text-sm">
							<div class="font-semibold text-slate-900">{{ $order->account->login }}</div>
							<div class="text-slate-500">{{ $order->account->game }} / {{ is_array($order->account->platform) ? implode(', ', $order->account->platform) : $order->account->platform }}</div>
							<a class="text-sm font-semibold text-slate-900 underline" href="{{ route('admin.accounts.show', $order->account) }}">Открыть аккаунт</a>
						</div>
					</div>
				@endif
			</div>
		</x-admin.card>

		<x-admin.card title="Выдача">
			<div class="space-y-4">
				<div class="space-y-1">
					<label class="text-xs font-medium text-slate-500">Оператор</label>
					<select wire:model="operatorTelegramId"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
						<option value="">— выберите оператора —</option>
						@foreach($operators as $operator)
							<option value="{{ $operator->telegram_id }}">
								{{ $operator->username ?: $operator->first_name }} ({{ $operator->telegram_id }})
							</option>
						@endforeach
					</select>
				</div>

				<div class="space-y-1">
					<label class="text-xs font-medium text-slate-500">Игра</label>
					<input type="text" wire:model="game"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
						placeholder="GTA">
				</div>

				<div class="space-y-1">
					<label class="text-xs font-medium text-slate-500">Платформа выдачи</label>
					<input type="text" list="issue-platform-options" wire:model="issuePlatform"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
					<datalist id="issue-platform-options">
						@foreach($issuePlatformOptions as $platform)
							<option value="{{ $platform }}"></option>
						@endforeach
					</datalist>
				</div>

				<x-admin.button variant="primary" wire:click="assignAccount" class="w-full">
					Выдать аккаунт
				</x-admin.button>

				<p class="text-xs text-slate-500">
					Сервис сам выбирает активный аккаунт по game/platform и списывает выдачу через общий IssueService.
				</p>
			</div>
		</x-admin.card>

		<x-admin.card title="Подключение">
			<div class="space-y-4">
				<div class="grid grid-cols-2 gap-2">
					<x-admin.field label="Попытки">{{ $order->connection_attempts_used }} / {{ $order->connection_attempts_limit }}</x-admin.field>
					<x-admin.field label="Блокировка">{{ $order->connection_locked_until?->format('d.m.Y H:i') ?? '—' }}</x-admin.field>
					<x-admin.field label="Последний код">{{ $order->last_connection_code ?: '—' }}</x-admin.field>
					<x-admin.field label="Код отправлен">{{ $order->last_connection_code_submitted_at?->format('d.m.Y H:i') ?? '—' }}</x-admin.field>
				</div>

				<div class="grid grid-cols-1 gap-2">
					<x-admin.button variant="secondary" wire:click="markOperatorConnecting" class="w-full">
						Оператор подключает
					</x-admin.button>
					<x-admin.button variant="primary" wire:click="markConnected" class="w-full">
						Подключено
					</x-admin.button>
				</div>

				<div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
					<div class="text-sm font-semibold text-slate-700">Ошибка подключения</div>
					<input type="text" wire:model="failReason"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
						placeholder="причина, если есть">
					<x-admin.button variant="danger" wire:click="markConnectionFailed" class="w-full">
						Отметить ошибку
					</x-admin.button>
				</div>

				<div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
					<div class="text-sm font-semibold text-slate-700">Дополнительные попытки</div>
					<input type="number" min="1" wire:model="extraAttempts"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
					<x-admin.button variant="secondary" wire:click="grantExtraAttempts" class="w-full">
						Добавить попытки
					</x-admin.button>
				</div>
			</div>
		</x-admin.card>
	</div>

	<x-admin.card title="События delivery">
		<div class="overflow-x-auto rounded-xl border border-slate-200">
			<table class="min-w-full text-sm">
				<thead class="bg-slate-50 border-b border-slate-200">
					<tr>
						<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Дата</th>
						<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Тип</th>
						<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Actor</th>
						<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Payload</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100 bg-white">
					@forelse($events as $event)
						<tr>
							<td class="px-4 py-3 text-slate-600">{{ $event->created_at?->format('d.m.Y H:i:s') }}</td>
							<td class="px-4 py-3 font-semibold text-slate-900">{{ $event->type }}</td>
							<td class="px-4 py-3 text-slate-600">
								{{ $event->actor_type ?: '—' }} {{ $event->actor_id ? '#'.$event->actor_id : '' }}
							</td>
							<td class="px-4 py-3">
								<pre class="max-w-xl whitespace-pre-wrap text-xs text-slate-500">@json($event->payload)</pre>
							</td>
						</tr>
					@empty
						<tr>
							<td class="px-4 py-10 text-center text-slate-500" colspan="4">Событий нет</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</x-admin.card>
</div>

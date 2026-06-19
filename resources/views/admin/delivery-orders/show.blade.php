@php($locked = $order->status->value === 'connected')
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
							@if(!in_array($order->status->value, ['cancelled', 'expired'], true))
					<div class="rounded-xl border border-red-200 bg-red-50 p-4 space-y-2">
						<div class="text-sm font-semibold text-red-700">Отмена заказа</div>
						<p class="text-xs text-slate-500">Для возврата/чарджбэка. Клиент потеряет доступ к данным на странице.</p>
						<x-admin.button variant="danger" wire:click="cancelOrder" wire:confirm="Отменить заказ? Клиент потеряет доступ к данным." class="w-full">Отменить заказ</x-admin.button>
					</div>
				@elseif($order->status->value === 'cancelled')
					<div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Заказ отменён{{ $order->cancelled_at ? ' · '.$order->cancelled_at->format('d.m.Y H:i') : '' }}.</div>
				@endif
</div>
		</x-admin.card>

		<x-admin.card title="Выдача">
			<div class="space-y-4">
				@if($locked)
				<div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
					✅ Заказ подключён и завершён. Поля зафиксированы.
				</div>
				@else
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
					<input type="text" list="delivery-game-options" wire:model.live.debounce.300ms="game"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
						placeholder="GTA">
					<datalist id="delivery-game-options">
						@foreach($availableGames as $gameOption)
							<option value="{{ $gameOption['name'] }}">
								{{ $gameOption['accounts_count'] }} available
							</option>
						@endforeach
					</datalist>

					@if(count($availableGames) > 0)
						<div class="max-h-36 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2">
							<div class="space-y-1">
								@foreach(array_slice($availableGames, 0, 12) as $gameOption)
									<button type="button"
										wire:click="$set('game', @js($gameOption['name']))"
										class="flex w-full items-center justify-between gap-3 rounded-lg px-2.5 py-2 text-left text-xs text-slate-700 hover:bg-white">
										<span class="truncate font-semibold">{{ $gameOption['name'] }}</span>
										<span class="shrink-0 text-slate-500">{{ $gameOption['accounts_count'] }} шт.</span>
									</button>
								@endforeach
							</div>
						</div>
					@else
						<p class="text-xs text-amber-700">
							Нет доступных аккаунтов для выбранной платформы выдачи.
						</p>
					@endif
				</div>

				<div class="space-y-1">
					<label class="text-xs font-medium text-slate-500">Платформа выдачи</label>
					<select wire:model.live="issuePlatform"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
						@foreach($issuePlatformOptions as $platform)
							<option value="{{ $platform }}">{{ $platform }}</option>
						@endforeach
					</select>
				</div>

				<div class="space-y-1">
					<label class="text-xs font-medium text-slate-500">Логин аккаунта</label>
					<select wire:model="selectedAccountId"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
						@disabled(count($availableAccounts) === 0)>
						<option value="">Любой логин (авто)</option>
						@foreach($availableAccounts as $accountOption)
							<option value="{{ $accountOption['id'] }}">
								{{ $accountOption['login'] }}@if(!empty($accountOption['platforms'])) · {{ implode(', ', $accountOption['platforms']) }}@endif
							</option>
						@endforeach
					</select>
					<p class="text-xs text-slate-500">
						Оставьте «авто» — система выберет аккаунт сама. Или укажите конкретный логин.
					</p>
				</div>

				<x-admin.button variant="primary" wire:click="assignAccount" wire:loading.attr="disabled" wire:target="assignAccount" class="w-full">
					<span wire:loading.remove wire:target="assignAccount">Выдать аккаунт</span>
					<span wire:loading wire:target="assignAccount">Выдача...</span>
				</x-admin.button>

				<p class="text-xs text-slate-500">
					Если нужной игры нет в списке, значит для выбранной платформы сейчас нет доступного аккаунта.
				</p>

				@if($order->account_id !== null && $order->issuance_id !== null)
					<div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
						<div class="text-sm font-semibold text-slate-700">Замена аккаунта</div>
						<select wire:model="replacementReason"
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
							<option value="wrong_password">Неверный пароль</option>
							<option value="no_access">Не входит</option>
							<option value="kicked">Клиента выбило</option>
							<option value="dead">Аккаунт умер</option>
							<option value="other">Другое</option>
						</select>
						<x-admin.button variant="secondary" wire:click="replaceAccount" wire:loading.attr="disabled" wire:target="replaceAccount" class="w-full">
							<span wire:loading.remove wire:target="replaceAccount">Выдать замену</span>
							<span wire:loading wire:target="replaceAccount">Выдача замены...</span>
						</x-admin.button>
						<p class="text-xs text-slate-500">
							Ссылка клиента не меняется. В этом заказе обновятся данные аккаунта и появится событие замены.
						</p>
					</div>
				@endif
				@endif
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

				@if($locked)
				<div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
					✅ Заказ завершён. Действия по подключению зафиксированы.
				</div>
				@else
				<div class="grid grid-cols-1 gap-2">
					<x-admin.button variant="secondary" wire:click="markOperatorConnecting" class="w-full" :disabled="$order->account_id === null">
						Оператор подключает
					</x-admin.button>
					<x-admin.button variant="primary" wire:click="markConnected" class="w-full" :disabled="$order->account_id === null">
						Подключено
					</x-admin.button>
				</div>

				@if($order->account_id === null)
					<p class="text-xs text-slate-500">Подключение станет доступно после выдачи аккаунта.</p>
				@endif

				<div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
					<div class="text-sm font-semibold text-slate-700">Ошибка подключения</div>
					<input type="text" wire:model="failReason"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
						placeholder="причина, если есть">
					<x-admin.button variant="danger" wire:click="markConnectionFailed" class="w-full" :disabled="$order->account_id === null">
						Отметить ошибку
					</x-admin.button>
				</div>

				<div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
					<div class="text-sm font-semibold text-slate-700">Дополнительные попытки</div>
					<input type="number" min="1" wire:model="extraAttempts"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
					<x-admin.button variant="secondary" wire:click="grantExtraAttempts" class="w-full" :disabled="$order->account_id === null">
						Добавить попытки
					</x-admin.button>
				</div>
				@endif
			</div>
		</x-admin.card>
	</div>

	<x-admin.card title="Дополнительные игры в заказе">
		<div class="space-y-4">
			@forelse($items as $it)
				<div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2">
					<div class="flex flex-wrap items-center gap-2">
						<span class="font-semibold text-slate-900">{{ $it->game ?: '—' }}</span>
						<x-admin.badge :variant="$this->statusVariant($it->status->value)">{{ $this->statusLabel($it->status->value) }}</x-admin.badge>
						<span class="text-xs text-slate-500">{{ $it->issue_platform ?: $it->platform }}</span>
					</div>
					<div class="grid grid-cols-1 gap-1 sm:grid-cols-2 text-sm">
						<x-admin.field label="Login">{{ $it->display_login ?: '—' }}</x-admin.field>
						<x-admin.field label="Password">{{ $it->display_password ?: '—' }}@if($it->display_password_type === 'fake') (fake)@endif</x-admin.field>
						<x-admin.field label="Код">{{ $it->last_connection_code ?: '—' }}</x-admin.field>
						<x-admin.field label="Попытки">{{ $it->connection_attempts_used }} / {{ $it->connection_attempts_limit }}</x-admin.field>
					</div>
					@if($it->status->value === 'connected')
						<div class="rounded-lg border border-green-200 bg-green-50 p-2 text-sm text-green-700">✅ Подключено. Поля зафиксированы.</div>
					@else
						<div class="flex flex-wrap gap-2">
							<x-admin.button variant="secondary" wire:click="itemConnecting({{ $it->id }})">Подключаю</x-admin.button>
							<x-admin.button variant="primary" wire:click="itemConnected({{ $it->id }})">Подключено</x-admin.button>
							<x-admin.button variant="secondary" wire:click="itemExtra({{ $it->id }})">+1 попытка</x-admin.button>
							<x-admin.button variant="danger" wire:click="itemFailed({{ $it->id }})">Ошибка</x-admin.button>
							<x-admin.button variant="secondary" wire:click="itemReplace({{ $it->id }})">Замена (dead)</x-admin.button>
						</div>
					@endif
				</div>
			@empty
				<p class="text-sm text-slate-500">Дополнительных игр нет. Первая игра — в блоке «Выдача» выше.</p>
			@endforelse

			@if($order->status->value !== 'connected' && $order->status->value !== 'cancelled' && $order->status->value !== 'expired')
				<div class="rounded-xl border border-slate-200 bg-white p-4 space-y-2">
					<div class="text-sm font-semibold text-slate-700">➕ Добавить игру</div>
					<input type="text" wire:model="newGame" placeholder="Название игры"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
					<select wire:model="newGamePlatform"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
						<option value="">Платформа выдачи…</option>
						@foreach($issuePlatformOptions as $platform)
							<option value="{{ $platform }}">{{ $platform }}</option>
						@endforeach
					</select>
					<x-admin.button variant="primary" wire:click="addGame" wire:loading.attr="disabled" wire:target="addGame" class="w-full">
						<span wire:loading.remove wire:target="addGame">Добавить игру</span>
						<span wire:loading wire:target="addGame">Добавление…</span>
					</x-admin.button>
				</div>
			@endif
		</div>
	</x-admin.card>

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

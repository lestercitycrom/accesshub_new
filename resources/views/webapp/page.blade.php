<div class="mx-auto max-w-3xl p-4 space-y-6">
		<header class="space-y-1">
			<h1 class="text-xl font-semibold">AccessHub WebApp</h1>
			<p class="text-sm text-gray-600">
				Открой в Telegram для авторизации. В dev можно включить ручной bootstrap.
			</p>
		</header>

		<div class="rounded-lg bg-white p-4 shadow-sm space-y-2">
			<div class="text-sm text-gray-700">
				@if($isBootstrapped)
					<span class="font-medium">Статус:</span> <span class="text-green-700">Готово</span>
				@else
					<span class="font-medium">Статус:</span> <span class="text-amber-700">Не инициализировано</span>
				@endif
			</div>

			<div id="bootstrapStatus" class="text-sm text-gray-600"></div>

			@if(!$isBootstrapped)
				<div class="text-xs text-gray-500">
					Подсказка: если открыто не в Telegram — нажми «Тестовый bootstrap» (только в dev).
				</div>
			@endif
		</div>

		<nav class="flex gap-2">
			<button
				type="button"
				class="rounded-md px-3 py-2 text-sm border border-gray-300 hover:bg-gray-100 {{ $tab === 'issue' ? 'bg-gray-100' : '' }}"
				wire:click="setTab('issue')"
			>
				Выдача
			</button>

			<button
				type="button"
				class="rounded-md px-3 py-2 text-sm border border-gray-300 hover:bg-gray-100 {{ $tab === 'history' ? 'bg-gray-100' : '' }}"
				wire:click="setTab('history')"
			>
				История
			</button>
		</nav>

		@if($tab === 'issue')
			<section class="rounded-lg bg-white p-4 shadow-sm space-y-4">
				<h2 class="text-base font-semibold">Выдача</h2>

			<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
				<div class="space-y-1">
					<label class="text-sm font-medium">Номер заказа</label>
					<input class="w-full rounded-md border-gray-300" type="text" wire:model="orderId">
				</div>

				<div class="space-y-1">
					<label class="text-sm font-medium">Количество</label>
					<input class="w-full rounded-md border-gray-300" type="number" min="1" max="2" wire:model="qty">
				</div>

				<div class="space-y-1">
					<label class="text-sm font-medium">Платформа</label>
					<input class="w-full rounded-md border-gray-300" type="text" wire:model="platform">
				</div>

				<div class="space-y-1">
					<label class="text-sm font-medium">Игра</label>
					<input class="w-full rounded-md border-gray-300" type="text" wire:model="game">
				</div>
			</div>

				<div class="flex items-center gap-2">
					<button
						class="rounded-md bg-black px-4 py-2 text-white hover:opacity-90"
						type="button"
						wire:click="issue"
					>
						Выдать
					</button>

					@if($canDevBootstrap)
						<button
							id="devBootstrapBtn"
							class="rounded-md border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50"
							type="button"
						>
							Тестовый bootstrap
						</button>
					@endif
				</div>

				@if($resultText)
					<pre class="whitespace-pre-wrap rounded-md bg-gray-100 p-3 text-sm">{{ $resultText }}</pre>
				@endif
			</section>
		@endif

		@if($tab === 'history')
			<section class="rounded-lg bg-white p-4 shadow-sm space-y-3">
				<h2 class="text-base font-semibold">История (последние 20)</h2>

				@if($resultText)
					<pre class="whitespace-pre-wrap rounded-md bg-gray-100 p-3 text-sm">{{ $resultText }}</pre>
				@endif

				<div class="overflow-x-auto">
					<table class="min-w-full text-sm">
						<thead>
							<tr class="text-left text-gray-600">
								<th class="py-2 pr-3">Выдано</th>
								<th class="py-2 pr-3">Заказ</th>
								<th class="py-2 pr-3">Игра</th>
								<th class="py-2 pr-3">Платформа</th>
								<th class="py-2 pr-3">Кол-во</th>
								<th class="py-2 pr-3">Аккаунт</th>
								<th class="py-2 pr-3">Действия</th>
							</tr>
						</thead>
						<tbody>
							@forelse($history as $row)
								@php
									$accountId = (int) $row->account_id;
								@endphp
								<tr class="border-t align-top">
									<td class="py-2 pr-3">{{ $row->issued_at?->format('Y-m-d H:i') }}</td>
									<td class="py-2 pr-3">{{ $row->order_id }}</td>
									<td class="py-2 pr-3">{{ $row->game }}</td>
									<td class="py-2 pr-3">{{ $row->platform }}</td>
									<td class="py-2 pr-3">{{ $row->qty }}</td>
									<td class="py-2 pr-3">
										<div class="text-xs text-gray-600">#{{ $accountId }}</div>
										<div class="text-xs text-gray-500">Последнее событие: {{ $this->lastEventTypeFor($accountId) ?? '-' }}</div>
									</td>
									<td class="py-2 pr-3 space-y-2">
										<div class="flex flex-wrap gap-2">
											<button
												type="button"
												class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50"
												wire:click="markProblem({{ $accountId }}, 'wrong_password')"
											>
												Неверный пароль
											</button>

											<button
												type="button"
												class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50"
												wire:click="markProblem({{ $accountId }}, 'stolen')"
											>
												Украден
											</button>

											<button
												type="button"
												class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50"
												wire:click="markProblem({{ $accountId }}, 'temp_hold')"
											>
												Врем. проблема
											</button>

											<button
												type="button"
												class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50"
												wire:click="markProblem({{ $accountId }}, 'dead')"
											>
												Мёртвый
											</button>

											<button
												type="button"
												class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50"
												wire:click="openPasswordForm({{ $accountId }}, 'update')"
											>
												Обновить пароль
											</button>
										</div>

										@if($passwordAccountId === $accountId)
											<div class="rounded-md bg-gray-50 p-2 space-y-2">
												<div class="text-xs text-gray-600">
													Новый пароль для аккаунта #{{ $accountId }}
													@if($passwordMode === 'recover_stolen')
														<span class="text-amber-700">(восстановить STOLEN)</span>
													@endif
												</div>

												<input
													class="w-full rounded-md border-gray-300 text-sm"
													type="text"
													wire:model="newPassword"
													placeholder="Новый пароль"
												>

												<div class="flex gap-2">
													<button
														type="button"
														class="rounded-md bg-black px-3 py-1 text-xs text-white hover:opacity-90"
														wire:click="submitPassword"
													>
														Сохранить
													</button>

													<button
														type="button"
														class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-100"
														wire:click="cancelPasswordForm"
													>
														Отмена
													</button>
												</div>
											</div>
										@endif
									</td>
								</tr>
							@empty
								<tr class="border-t">
									<td class="py-3 text-gray-500" colspan="7">Нет данных</td>
								</tr>
							@endforelse
						</tbody>
					</table>
				</div>

				@if($stolenAccounts->count() > 0)
					<div class="pt-4">
						<h3 class="text-sm font-semibold">STOLEN аккаунты, закреплённые за вами</h3>

						<div class="overflow-x-auto">
							<table class="min-w-full text-sm">
								<thead>
									<tr class="text-left text-gray-600">
										<th class="py-2 pr-3">Аккаунт</th>
										<th class="py-2 pr-3">Логин</th>
										<th class="py-2 pr-3">Дедлайн</th>
										<th class="py-2 pr-3">Действия</th>
									</tr>
								</thead>
								<tbody>
									@foreach($stolenAccounts as $stolen)
										<tr class="border-t align-top">
											<td class="py-2 pr-3">#{{ $stolen->id }}</td>
											<td class="py-2 pr-3"><code>{{ $stolen->login }}</code></td>
											<td class="py-2 pr-3">{{ $stolen->status_deadline_at?->format('Y-m-d H:i') ?? '-' }}</td>
											<td class="py-2 pr-3 space-y-2">
												<div class="flex flex-wrap gap-2">
													<button
														type="button"
														class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50"
														wire:click="openPasswordForm({{ $stolen->id }}, 'recover_stolen')"
													>
														Восстановлен
													</button>

													<button
														type="button"
														class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50"
														wire:click="postponeStolen({{ $stolen->id }})"
													>
														Перенести на 1 день
													</button>
												</div>
											</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				@endif
			</section>
		@endif
	</div>

<script>
	(function () {
		const statusEl = document.getElementById('bootstrapStatus');
		const devBtn = document.getElementById('devBootstrapBtn');

		async function bootstrap(payload) {
			const res = await fetch('/webapp/bootstrap', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
				},
				body: JSON.stringify(payload),
				credentials: 'same-origin',
			});

			if (res.status === 204) {
				statusEl.textContent = 'Bootstrap: готово';
				window.location.reload();
				return;
			}

			const txt = await res.text();
			statusEl.textContent = 'Bootstrap: ' + res.status + ' ' + txt;
		}

		try {
			const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;

			if (tg && typeof tg.initData === 'string' && tg.initData.length > 0) {
				statusEl.textContent = 'Bootstrap: initData получены...';
				bootstrap({ initData: tg.initData });
			} else {
				statusEl.textContent = 'Bootstrap: Telegram не обнаружен.';
			}
		} catch (e) {
			statusEl.textContent = 'Bootstrap: ошибка';
		}

		if (devBtn) {
			devBtn.addEventListener('click', function () {
				bootstrap({
					telegram_id: 111,
					username: 'dev_user',
					first_name: 'Dev',
					last_name: 'User'
				});
			});
		}
	})();
</script>

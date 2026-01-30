<div class="space-y-6">
	<div class="flex flex-wrap items-start justify-between gap-3">
		<div>
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">
				{{ $isEdit ? 'Редактирование аккаунта' : 'Создание аккаунта' }}
			</h1>
			<p class="text-sm text-slate-500">
				{{ $isEdit ? 'Обновите данные аккаунта и сохраните изменения.' : 'Заполните данные для нового аккаунта.' }}
			</p>
		</div>

		<div class="flex items-center gap-2">
			@if($isEdit && isset($account))
				<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
					href="{{ route('admin.accounts.show', $account) }}">
					Открыть
				</a>
			@endif

			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.accounts.index') }}">
				К списку
			</a>
		</div>
	</div>

	@if(session('status'))
		<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
			{{ session('status') }}
		</div>
	@endif

	@if($errors->any())
		<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
			<div class="font-semibold">Ошибки валидации</div>
			<ul class="mt-2 list-disc pl-5 space-y-1">
				@foreach($errors->all() as $err)
					<li class="text-xs">{{ $err }}</li>
				@endforeach
			</ul>
		</div>
	@endif

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
		<!-- Main -->
		<div class="lg:col-span-2 space-y-6">
			<x-admin.card title="Основное">
				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<x-admin.input
						label="Игра"
						type="text"
						placeholder="cs2"
						name="game"
						:value="old('game', $game ?? '')"
						wire:model="game"
						:error="$errors->first('game')"
					/>

					<div class="space-y-1">
						<label class="text-xs font-semibold text-slate-700">Платформа</label>
						<select wire:model="platformSelected" multiple
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200 min-h-[100px]">
							@foreach($platformOptions ?? [] as $p)
								<option value="{{ $p }}">{{ $p }}</option>
							@endforeach
						</select>
						@error('platformSelected') <div class="text-xs font-medium text-rose-600">{{ $message }}</div> @enderror
						<div class="text-xs text-slate-500">Удерживайте Ctrl (Cmd на Mac), чтобы выбрать несколько платформ.</div>
					</div>

					<div class="sm:col-span-2">
						<x-admin.input
							label="Логин"
							type="text"
							placeholder="Введите логин"
							name="login"
							:value="old('login', $login ?? '')"
							wire:model="login"
							:error="$errors->first('login')"
						/>
					</div>

					<div class="sm:col-span-2">
						<x-admin.input
							label="Пароль"
							type="text"
							placeholder="пароль аккаунта"
							name="password"
							:value="old('password', $password ?? '')"
							wire:model="password"
							:error="$errors->first('password')"
							hint="Отображается и сохраняется в зашифрованном виде."
						/>
					</div>
				</div>
			</x-admin.card>

			<x-admin.card title="Mail Account">
				<div class="grid grid-cols-1 gap-4">
					<x-admin.input
						label="Логин почты"
						type="text"
						placeholder="Введите логин почты"
						wire:model="mailAccountLogin"
						:error="$errors->first('mailAccountLogin')"
					/>

					<x-admin.input
						label="Пароль почты"
						type="text"
						placeholder="Введите пароль почты"
						wire:model="mailAccountPassword"
						:error="$errors->first('mailAccountPassword')"
						hint="Отображается и сохраняется в зашифрованном виде."
					/>

					<x-admin.input
						label="Почта двухфакторной защиты"
						type="text"
						placeholder="Введите почту двухфакторной защиты"
						wire:model="twoFaMailAccountDate"
						:error="$errors->first('twoFaMailAccountDate')"
					/>

					<x-admin.input
						label="Код восстановления"
						type="text"
						placeholder="Введите код восстановления"
						wire:model="recoverCode"
						:error="$errors->first('recoverCode')"
					/>
				</div>
			</x-admin.card>

			<x-admin.card title="Comment">
				<div class="space-y-2">
					<label class="text-xs font-semibold text-slate-700">Comment</label>
					<textarea
						wire:model="comment"
						rows="4"
						class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
						placeholder="Комментарий к аккаунту..."
					></textarea>
					@error('comment') <div class="text-xs font-medium text-rose-600">{{ $message }}</div> @enderror
				</div>
			</x-admin.card>
		</div>

		<!-- Side -->
		<div class="space-y-6">
			<x-admin.card title="Статус">
				<div class="space-y-3">
					<div class="space-y-1">
						<label class="text-xs font-semibold text-slate-700">Статус</label>
						<select wire:model="status"
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
							@foreach($statuses as $s)
								<option value="{{ $s }}">{{ $s }}</option>
							@endforeach
						</select>
						@error('status') <div class="text-xs font-medium text-rose-600">{{ $message }}</div> @enderror
					</div>

					<div class="space-y-1">
						<label class="text-xs font-semibold text-slate-700">Назначен оператор</label>
						<select wire:model="assignedToTelegramId"
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
							<option value="">— не назначен</option>
							@foreach($operators ?? [] as $op)
								<option value="{{ $op->telegram_id }}">{{ $op->username ?: $op->first_name }} ({{ $op->telegram_id }})</option>
							@endforeach
						</select>
						@error('assignedToTelegramId') <div class="text-xs font-medium text-rose-600">{{ $message }}</div> @enderror
					</div>

					<div class="space-y-1">
						<label class="text-xs font-semibold text-slate-700">Дедлайн статуса</label>
						<input type="datetime-local" wire:model="statusDeadlineAt"
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
						@error('statusDeadlineAt') <div class="text-xs font-medium text-rose-600">{{ $message }}</div> @enderror
						<div class="text-xs text-slate-500">Дедлайн обязателен. Для STOLEN/RECOVERY нужно указывать дедлайн.</div>
					</div>
				</div>
			</x-admin.card>

			<x-admin.card title="Флаги">
				<div class="space-y-3">
					<label class="text-xs font-semibold text-slate-700">Проблемные метки</label>

					<div class="space-y-2">
						<label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5">
							<input type="checkbox" wire:model="flagActionRequired" class="rounded border-slate-300">
							<span class="text-sm text-slate-700">Требуются действия</span>
						</label>

						<label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5">
							<input type="checkbox" wire:model="flagPasswordUpdateRequired" class="rounded border-slate-300">
							<span class="text-sm text-slate-700">Требуется обновление пароля</span>
						</label>
					</div>

					<p class="text-xs text-slate-500">
						Флаги помечают аккаунт как проблемный и блокируют выдачу до решения проблемы.
					</p>
				</div>
			</x-admin.card>

			<x-admin.card title="Сохранение">
				<div class="flex items-center gap-2">
					<x-admin.button variant="primary" size="md" wire:click="save">
						Сохранить
					</x-admin.button>

					<x-admin.button variant="secondary" size="md" onclick="window.location='{{ route('admin.accounts.index') }}'">
						Отмена
					</x-admin.button>
				</div>

				<p class="mt-3 text-xs text-slate-500">
					Сохранение применяется сразу. Проверьте данные перед сохранением.
				</p>
			</x-admin.card>
		</div>
	</div>
</div>

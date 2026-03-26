<div class="space-y-6">
	<div class="flex flex-wrap items-start justify-between gap-3">
		<div>
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">Настройки</h1>
			<p class="text-sm text-slate-500">Минимальные настройки проекта, редактируемые из админки.</p>
		</div>

		<div class="flex items-center gap-2">
			<x-admin.button variant="secondary" size="md" wire:click="$refresh">
				Обновить
			</x-admin.button>
		</div>
	</div>

	@if(session('status') || $successMessage)
		<x-admin.alert variant="success" :message="$successMessage ?? session('status')" />
	@endif

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
		<div class="lg:col-span-2 space-y-6">
			<x-admin.card title="Общее">
				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<x-admin.input
						label="Дней кулдауна"
						type="number"
						min="0"
						wire:model="cooldownDays"
						:error="$errors->first('cooldownDays')"
						hint="Сколько дней аккаунт недоступен после исчерпания лимита выдач."
					/>

					<x-admin.input
						label="Макс. аккаунтов за запрос"
						type="number"
						min="1"
						wire:model="maxQty"
						:error="$errors->first('maxQty')"
						hint="Максимальное количество аккаунтов, выдаваемых за один запрос."
					/>

					<div class="sm:col-span-2">
						<x-admin.input
							label="Дедлайн «Украден» (дней)"
							type="number"
							min="1"
							wire:model="stolenDefaultDeadlineDays"
							:error="$errors->first('stolenDefaultDeadlineDays')"
							hint="Сколько дней даётся оператору на работу с украденным аккаунтом."
						/>
					</div>
				</div>

				<div class="mt-6 flex items-center gap-2">
					<x-admin.button variant="primary" size="md" wire:click="save">
						Сохранить
					</x-admin.button>

					<x-admin.button
						variant="secondary"
						size="md"
						type="button"
						onclick="window.location='{{ route('admin.settings.index') }}'">
						Отмена
					</x-admin.button>
				</div>
			</x-admin.card>

			<x-admin.card title="Telegram WebApp">
				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<div class="sm:col-span-2">
						<x-admin.input
							label="URL WebApp"
							type="text"
							wire:model="webappMenuUrl"
							:error="$errors->first('webappMenuUrl')"
							hint="Полный URL, например https://<домен>/webapp"
						/>
					</div>

					<div class="sm:col-span-2">
						<x-admin.input
							label="Название кнопки меню"
							type="text"
							wire:model="webappMenuText"
							:error="$errors->first('webappMenuText')"
							hint="Текст кнопки меню в Telegram (до 64 символов)"
						/>
					</div>

					<div class="sm:col-span-2">
						<x-admin.select
							label="Где показывать результат выдачи"
							wire:model="webappIssueDelivery"
							:error="$errors->first('webappIssueDelivery')"
							hint="Выбор: окно WebApp, чат Telegram или оба варианта."
						>
							<option value="webapp">Только в окне WebApp</option>
							<option value="chat">Только в чате Telegram</option>
							<option value="both">И в окне WebApp, и в чате</option>
						</x-admin.select>
					</div>
				</div>

				<div class="mt-6 flex items-center gap-2">
					<x-admin.button variant="primary" size="md" wire:click="applyWebAppMenu">
						Установить кнопку WebApp
					</x-admin.button>
				</div>
			</x-admin.card>

			<x-admin.card title="Примечания">
				<div class="space-y-2 text-sm text-slate-600">
					<p class="font-semibold text-slate-900">Как использовать</p>
					<ul class="list-disc pl-5 space-y-1 text-xs text-slate-500">
						<li>«Дней кулдауна» — сколько дней аккаунт недоступен после исчерпания лимита выдач.</li>
						<li>«Дедлайн Украден» — срок, за который оператор должен отработать украденный аккаунт.</li>
						<li>«Макс. аккаунтов за запрос» — ограничение количества аккаунтов в одной выдаче.</li>
					</ul>

					<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
						<div class="text-xs font-medium text-slate-400">Подсказка</div>
						<div class="mt-1 text-xs text-slate-500">
							Если настройки ещё не подключены в бизнес-логику — они всё равно сохраняются и готовы к использованию.
						</div>
					</div>
				</div>
			</x-admin.card>
		</div>

		<div class="space-y-6">
			<x-admin.card title="Безопасность">
				<div class="space-y-2 text-sm text-slate-600">
					<p class="font-semibold text-slate-900">Рекомендации</p>
					<ul class="list-disc pl-5 space-y-1 text-xs text-slate-500">
						<li>Изменяй параметры только админом.</li>
						<li>После изменений — проверь выдачу/проблемные сценарии на тестовом аккаунте.</li>
						<li>Логи изменения можно расширить позже (audit).</li>
					</ul>
				</div>
			</x-admin.card>

			<x-admin.card title="Быстрые ссылки">
				<div class="flex flex-col gap-2">
					<a class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50"
						href="{{ route('admin.problems.index') }}">
						Проблемные
					</a>

					<a class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50"
						href="{{ route('admin.issuances.index') }}">
						Выдачи
					</a>

					<a class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50"
						href="{{ route('admin.events.index') }}">
						События
					</a>
				</div>
			</x-admin.card>
		</div>
	</div>

	<!-- Danger Zone -->
	<div class="rounded-2xl border-2 border-red-200 bg-red-50 p-6">
		<div class="flex flex-col gap-4">
			<div>
				<h3 class="text-lg font-semibold text-red-900">Опасная зона</h3>
				<p class="mt-1 text-sm text-red-700">
					Удаление всех аккаунтов — необратимая операция. Все данные будут безвозвратно удалены.
				</p>
			</div>

			<div class="flex flex-wrap items-end gap-3">
				<div class="w-64">
					<x-admin.input
						label="Подтверждение паролем"
						type="password"
						wire:model="confirmPassword"
						placeholder="Введите ваш пароль"
						:error="$errors->first('confirmPassword')"
					/>
				</div>

				<x-admin.button
					variant="danger"
					size="md"
					wire:click="deleteAllAccounts"
					onclick="if(!confirm('Вы уверены, что хотите удалить ВСЕ аккаунты? Это действие необратимо!')){event.preventDefault();event.stopImmediatePropagation();}"
				>
					Удалить все аккаунты
				</x-admin.button>
			</div>
		</div>
	</div>
</div>

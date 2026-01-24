<div class="space-y-6">
	<div class="flex flex-wrap items-center justify-between gap-3">
		<div>
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">
				{{ $telegramUser ? 'Редактировать пользователя Telegram' : 'Создать пользователя Telegram' }}
			</h1>
			<p class="text-sm text-slate-500">Редактирование данных и прав доступа.</p>
		</div>

		<div class="flex items-center gap-2">
			<x-admin.button variant="secondary" onclick="window.location='{{ route('admin.telegram-users.index') }}'">
				Назад
			</x-admin.button>
		</div>
	</div>

	<x-admin.card title="Данные пользователя">
		<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
			<x-admin.input
				label="Telegram ID"
				type="number"
				wire:model="telegramId"
				:error="$errors->first('telegramId')"
			/>

			<x-admin.input
				label="Username"
				type="text"
				wire:model="username"
				:error="$errors->first('username')"
			/>

			<x-admin.input
				label="First name"
				type="text"
				wire:model="firstName"
				:error="$errors->first('firstName')"
			/>

			<x-admin.input
				label="Last name"
				type="text"
				wire:model="lastName"
				:error="$errors->first('lastName')"
			/>

			<x-admin.select
				label="Роль"
				wire:model="role"
				:error="$errors->first('role')"
			>
				<option value="operator">operator</option>
				<option value="admin">admin</option>
			</x-admin.select>

			<div class="space-y-1">
				<label class="text-xs font-semibold text-slate-700">Активен</label>
				<div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5">
					<input type="checkbox" wire:model="isActive" class="rounded border-slate-300">
					<span class="text-sm text-slate-700">Активен</span>
				</div>
			</div>
		</div>

		<div class="mt-6 flex items-center gap-2">
			<x-admin.button variant="primary" wire:click="save">
				Сохранить
			</x-admin.button>

			<x-admin.button variant="secondary" onclick="window.location='{{ route('admin.telegram-users.index') }}'">
				Отмена
			</x-admin.button>
		</div>
	</x-admin.card>
</div>

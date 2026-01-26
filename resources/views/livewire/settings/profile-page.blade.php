<div class="space-y-6">
	<x-admin.page-header
		title="Профиль"
		subtitle="Редактирование данных аккаунта и смена пароля."
	>
		<x-slot:breadcrumbs>
			<span class="text-slate-500">Админ</span>
			<span class="px-1 text-slate-300">/</span>
			<span class="font-semibold text-slate-700">Профиль</span>
		</x-slot:breadcrumbs>
	</x-admin.page-header>

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
		<x-admin.card title="Данные аккаунта">
			<form wire:submit="updateProfileInformation" class="space-y-6">
				<x-admin.input
					label="Имя"
					type="text"
					wire:model="name"
					required
					autofocus
					autocomplete="name"
					:error="$errors->first('name')"
				/>

				<div>
					<x-admin.input
						label="Email"
						type="email"
						wire:model="email"
						required
						autocomplete="email"
						:error="$errors->first('email')"
					/>

					@if ($this->hasUnverifiedEmail)
						<div class="mt-4">
							<x-admin.alert variant="warning" :autohide="false" :dismissible="false">
								<div class="font-semibold">Email не подтверждён</div>
								<div class="mt-1 text-sm">
									<button type="button" class="underline" wire:click.prevent="resendVerificationNotification">
										Отправить письмо подтверждения повторно
									</button>
								</div>
							</x-admin.alert>

							@if (session('status') === 'verification-link-sent')
								<x-admin.alert class="mt-3" variant="success" :autohide="false" :dismissible="false" message="Новая ссылка подтверждения отправлена на ваш email." />
							@endif
						</div>
					@endif
				</div>

				<div class="flex items-center gap-4">
					<x-admin.button variant="primary" type="submit" data-test="update-profile-button">
						Сохранить
					</x-admin.button>

					<x-action-message class="text-sm text-slate-600" on="profile-updated">
						Сохранено.
					</x-action-message>
				</div>
			</form>
		</x-admin.card>

		<x-admin.card title="Смена пароля">
			<form wire:submit="updatePassword" class="space-y-6">
				<x-admin.input
					label="Текущий пароль"
					type="password"
					wire:model="current_password"
					required
					autocomplete="current-password"
					:error="$errors->first('current_password')"
				/>
				<x-admin.input
					label="Новый пароль"
					type="password"
					wire:model="password"
					required
					autocomplete="new-password"
					:error="$errors->first('password')"
				/>
				<x-admin.input
					label="Подтвердите пароль"
					type="password"
					wire:model="password_confirmation"
					required
					autocomplete="new-password"
				/>

				<div class="flex items-center gap-4">
					<x-admin.button variant="primary" type="submit" data-test="update-password-button">
						Сохранить
					</x-admin.button>

					<x-action-message class="text-sm text-slate-600" on="password-updated">
						Сохранено.
					</x-action-message>
				</div>
			</form>
		</x-admin.card>
	</div>
</div>

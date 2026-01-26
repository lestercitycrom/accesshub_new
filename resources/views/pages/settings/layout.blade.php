<div class="flex items-start gap-6 max-md:flex-col">
	<div class="w-full md:w-[240px]">
		<x-admin.card title="Разделы">
			<nav class="flex flex-col gap-2">
				@php
					$isProfile = request()->routeIs('profile.edit');
					$isPassword = request()->routeIs('user-password.edit');
					$isTwoFactor = request()->routeIs('two-factor.show');
					$isAppearance = request()->routeIs('appearance.edit');
				@endphp

				<a href="{{ route('profile.edit') }}"
					class="rounded-xl px-3 py-2 text-sm font-semibold border transition
						{{ $isProfile ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
					Профиль
				</a>
				<a href="{{ route('user-password.edit') }}"
					class="rounded-xl px-3 py-2 text-sm font-semibold border transition
						{{ $isPassword ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
					Пароль
				</a>
				@if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
					<a href="{{ route('two-factor.show') }}"
						class="rounded-xl px-3 py-2 text-sm font-semibold border transition
							{{ $isTwoFactor ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
						Двухфакторная аутентификация
					</a>
				@endif
				<a href="{{ route('appearance.edit') }}"
					class="rounded-xl px-3 py-2 text-sm font-semibold border transition
						{{ $isAppearance ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
					Внешний вид
				</a>
			</nav>
		</x-admin.card>
	</div>

	<div class="flex-1 self-stretch">
		<x-admin.card>
			@if(!empty($heading))
				<div class="text-sm font-semibold text-slate-900">{{ $heading }}</div>
			@endif
			@if(!empty($subheading))
				<div class="mt-1 text-sm text-slate-500">{{ $subheading }}</div>
			@endif

			<div class="mt-5 w-full max-w-lg">
				{{ $slot }}
			</div>
		</x-admin.card>
	</div>
</div>

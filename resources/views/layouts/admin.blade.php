<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	@livewireStyles
	<title>@yield('title', 'AccessHub Admin')</title>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
	<header class="sticky top-0 z-40 bg-slate-900 text-slate-100 border-b border-white/10">
		<div class="mx-auto max-w-7xl px-4">
			<div class="h-16 flex items-center justify-between gap-4">
				<!-- Brand -->
				<a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
					<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 font-semibold">
						AH
					</span>
					<span class="font-semibold tracking-wide">AccessHub Admin</span>
				</a>

				<!-- Nav -->
				<nav class="hidden md:flex items-center gap-1">
					@php
						$nav = [
							['label' => 'Telegram Users', 'route' => 'admin.telegram-users.index'],
							['label' => 'Accounts', 'route' => 'admin.accounts.index'],
							['label' => 'Lookup', 'route' => 'admin.account-lookup'],
							['label' => 'Import', 'route' => 'admin.import.accounts'],
							['label' => 'Issuances', 'route' => 'admin.issuances.index'],
							['label' => 'Events', 'route' => 'admin.events.index'],
							['label' => 'Problems', 'route' => 'admin.problems.index'],
							['label' => 'Settings', 'route' => 'admin.settings.index'],
						];
					@endphp

					@foreach($nav as $item)
						@if(\Illuminate\Support\Facades\Route::has($item['route']))
							@php
								$isActive = request()->routeIs($item['route']);
							@endphp

							<a
								href="{{ route($item['route']) }}"
								class="rounded-xl px-3 py-2 text-sm font-semibold transition
									{{ $isActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
							>
								{{ $item['label'] }}
							</a>
						@endif
					@endforeach
				</nav>

				<!-- Right -->
				<div class="flex items-center gap-3">
					<div class="hidden sm:flex items-center gap-2 text-sm text-slate-200">
						<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white font-semibold">
							{{ strtoupper(substr((string) auth()->user()?->name, 0, 1)) }}
						</span>
						<span class="font-medium">{{ auth()->user()?->name }}</span>
					</div>

					<form method="POST" action="{{ route('logout') }}">
						@csrf
						<button
							type="submit"
							class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold
								bg-white text-slate-900 hover:bg-slate-100 active:bg-slate-200"
						>
							Logout
						</button>
					</form>
				</div>
			</div>

			<!-- Mobile nav -->
			<div class="md:hidden pb-3">
				<div class="flex flex-wrap gap-1">
					@foreach($nav as $item)
						@if(\Illuminate\Support\Facades\Route::has($item['route']))
							@php
								$isActive = request()->routeIs($item['route']);
							@endphp

							<a
								href="{{ route($item['route']) }}"
								class="rounded-xl px-3 py-2 text-sm font-semibold transition
									{{ $isActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
							>
								{{ $item['label'] }}
							</a>
						@endif
					@endforeach
				</div>
			</div>
		</div>
	</header>

	<main class="mx-auto max-w-7xl px-4 py-6">
		{{ $slot }}
	</main>

	@livewireScripts
</body>
</html>
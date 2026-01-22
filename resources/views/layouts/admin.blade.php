<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	@vite(['resources/css/app.css', 'resources/js/app.js'])
	@livewireStyles

	<title>@yield('title', config('admin-kit.brand.name', 'Admin'))</title>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
	<header class="sticky top-0 z-40 bg-gradient-to-r from-slate-900 to-slate-800 text-slate-100 border-b border-white/10">
		<div class="mx-auto {{ config('admin-kit.layout.container', 'max-w-7xl') }} px-4">
			<div class="h-16 flex items-center justify-between gap-4">
				<a href="{{ \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/') }}" class="flex items-center gap-2">
					<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 font-semibold">
						{{ config('admin-kit.brand.badge', 'AK') }}
					</span>
					<span class="font-semibold tracking-wide">{{ config('admin-kit.brand.name', 'Admin') }}</span>
				</a>

				{{-- Global search --}}
				@if(\Illuminate\Support\Facades\Route::has('admin.accounts.lookup'))
					<form method="GET" action="{{ route('admin.accounts.lookup') }}" class="hidden lg:flex items-center gap-2 min-w-0">
						<div class="relative">
							<span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300">
								<x-admin.icon name="search" class="h-4 w-4" />
							</span>
							<input
								type="text"
								name="q"
								value="{{ request('q') }}"
								placeholder="Search login / id / order..."
								class="w-[360px] rounded-xl bg-white/10 border border-white/10 pl-10 pr-3 py-2 text-sm text-white placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-white/20"
							/>
						</div>

						<button
							type="submit"
							class="rounded-xl px-3 py-2 text-sm font-semibold bg-white text-slate-900 hover:bg-slate-100"
						>
							Go
						</button>
					</form>
				@endif

				<nav class="hidden md:flex items-center gap-1">
					@foreach((array) config('admin-kit.nav', []) as $item)
						@php
							$route = (string) ($item['route'] ?? '');
							$label = (string) ($item['label'] ?? '');
							$icon = (string) ($item['icon'] ?? '');
						@endphp

						@if($route !== '' && $label !== '' && \Illuminate\Support\Facades\Route::has($route))
							@php $isActive = request()->routeIs($route); @endphp
							<a
								href="{{ route($route) }}"
								class="rounded-xl px-3 py-2 text-sm font-semibold transition inline-flex items-center gap-2
									{{ $isActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
							>
								@if($icon !== '')
									<x-admin.icon :name="$icon" class="h-4 w-4" />
								@endif
								<span>{{ $label }}</span>
							</a>
						@endif
					@endforeach
				</nav>

				<div class="flex items-center gap-3">
					{{-- Quick actions --}}
					@php
						$qa = (array) config('admin-kit.quick_actions', []);
					@endphp

					@if(count($qa) > 0)
						<details class="relative hidden sm:block">
							<summary class="list-none cursor-pointer select-none rounded-xl px-3 py-2 text-sm font-semibold bg-white/10 hover:bg-white/15 inline-flex items-center gap-2">
								<span class="text-slate-100">Actions</span>
								<span class="text-slate-300">▾</span>
							</summary>

							<div class="absolute right-0 mt-2 w-60 rounded-2xl border border-white/10 bg-slate-900/95 backdrop-blur shadow-lg overflow-hidden">
								<div class="p-2">
									@foreach($qa as $item)
										@php
											$route = (string) ($item['route'] ?? '');
											$label = (string) ($item['label'] ?? '');
											$icon = (string) ($item['icon'] ?? '');
										@endphp

										@if($route !== '' && $label !== '' && \Illuminate\Support\Facades\Route::has($route))
											<a
												href="{{ route($route) }}"
												class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-100 hover:bg-white/10"
											>
												@if($icon !== '')
													<x-admin.icon :name="$icon" class="h-4 w-4 text-slate-300" />
												@endif
												<span>{{ $label }}</span>
											</a>
										@endif
									@endforeach
								</div>
							</div>
						</details>
					@endif

					<div class="hidden sm:flex items-center gap-2 text-sm text-slate-200">
						<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white font-semibold">
							{{ strtoupper(substr((string) auth()->user()?->name, 0, 1)) }}
						</span>
						<span class="font-medium">{{ auth()->user()?->name }}</span>
					</div>

					@if(\Illuminate\Support\Facades\Route::has('logout'))
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
					@endif
				</div>
			</div>

			{{-- Mobile nav --}}
			<div class="md:hidden pb-3">
				<div class="flex flex-wrap gap-1">
					@foreach((array) config('admin-kit.nav', []) as $item)
						@php
							$route = (string) ($item['route'] ?? '');
							$label = (string) ($item['label'] ?? '');
							$icon = (string) ($item['icon'] ?? '');
						@endphp

						@if($route !== '' && $label !== '' && \Illuminate\Support\Facades\Route::has($route))
							@php $isActive = request()->routeIs($route); @endphp
							<a
								href="{{ route($route) }}"
								class="rounded-xl px-3 py-2 text-sm font-semibold transition inline-flex items-center gap-2
									{{ $isActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
							>
								@if($icon !== '')
									<x-admin.icon :name="$icon" class="h-4 w-4" />
								@endif
								<span>{{ $label }}</span>
							</a>
						@endif
					@endforeach
				</div>

				{{-- Mobile quick actions --}}
				@if(count($qa) > 0)
					<div class="mt-2 flex flex-wrap gap-1">
						@foreach($qa as $item)
							@php
								$route = (string) ($item['route'] ?? '');
								$label = (string) ($item['label'] ?? '');
							@endphp

							@if($route !== '' && $label !== '' && \Illuminate\Support\Facades\Route::has($route))
								<a
									href="{{ route($route) }}"
									class="rounded-xl px-3 py-2 text-sm font-semibold bg-white/10 hover:bg-white/15 text-slate-100"
								>
									{{ $label }}
								</a>
							@endif
						@endforeach
					</div>
				@endif
			</div>
		</div>
	</header>

	<main class="mx-auto {{ config('admin-kit.layout.container', 'max-w-7xl') }} px-4 py-6">
		{{ $slot ?? '' }}
		@yield('content')
	</main>

	@livewireScripts
</body>
</html>
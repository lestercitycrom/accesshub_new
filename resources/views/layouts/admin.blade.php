<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	@vite(['resources/css/app.css', 'resources/js/app.js'])
	@livewireStyles

	<link rel="icon" href="/accesshub_logo_strict_2_plane_lock_128.png" type="image/png">
	<link rel="apple-touch-icon" href="/accesshub_logo_strict_2_plane_lock_128.png">

	<title>@yield('title', config('app.name'))</title>
	<style>[x-cloak]{display:none !important;}</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
	@php
		// Hide nav items the current user's role can't access. Items may carry a
		// `can` gate; children are filtered too. Security is enforced by route
		// middleware — this is UX so operators/viewers don't see dead links.
		$navAllowed = static fn ($item): bool => empty($item['can']) || (bool) (auth()->user()?->can($item['can']));
		$navItems = [];
		foreach ((array) config('admin-kit.nav', []) as $navItem) {
			if (!$navAllowed($navItem)) {
				continue;
			}
			if (!empty($navItem['children'])) {
				$navItem['children'] = array_values(array_filter($navItem['children'], $navAllowed));
			}
			$navItems[] = $navItem;
		}
		$userMenuItems = array_values(array_filter((array) config('admin-kit.user_menu', []), $navAllowed));
	@endphp
	<header class="sticky top-0 z-40 bg-gradient-to-r from-slate-900 to-slate-800 text-slate-100 border-b border-white/10">
		<div class="mx-auto {{ config('admin-kit.layout.container', 'max-w-7xl') }} px-4">
			<div class="h-16 flex min-w-0 items-center justify-between gap-4">
				<a href="{{ route('admin.accounts.index') }}" class="flex shrink-0 items-center gap-2">
					<img src="/accesshub_logo_strict_2_plane_lock_128.png" alt="" class="h-9 w-9 rounded-xl object-contain" width="36" height="36">
					<span class="font-semibold tracking-wide">{{ config('app.name') }}</span>
				</a>

				{{-- Global search --}}
				@if(config('admin-kit.features.global_search') && \Illuminate\Support\Facades\Route::has(config('admin-kit.global_search.route')))
					<form method="GET" action="{{ route(config('admin-kit.global_search.route')) }}" class="hidden lg:flex items-center gap-2 min-w-0">
						<div class="relative">
							<span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300">
								<x-admin.icon name="search" class="h-4 w-4" />
							</span>
							<input
								type="text"
								name="{{ config('admin-kit.global_search.query_key', 'q') }}"
								value="{{ request(config('admin-kit.global_search.query_key', 'q')) }}"
								placeholder="{{ config('admin-kit.global_search.placeholder', 'Поиск...') }}"
								class="w-[360px] rounded-xl bg-white/10 border border-white/10 pl-10 pr-3 py-2 text-sm text-white placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-white/20"
							/>
						</div>

						<button
							type="submit"
							class="rounded-xl px-3 py-2 text-sm font-semibold bg-white text-slate-900 hover:bg-slate-100"
						>
							Найти
						</button>
					</form>
				@endif

				<nav class="hidden min-w-0 flex-1 items-center gap-1 md:flex">
					@foreach($navItems as $item)
						<x-admin.nav-item :item="$item" />
					@endforeach
				</nav>

				<div class="flex shrink-0 items-center gap-3">
					{{-- Quick actions --}}
					@if(config('admin-kit.features.quick_actions') && count((array) config('admin-kit.quick_actions', [])) > 0)
					@php
						$qa = (array) config('admin-kit.quick_actions', []);
					@endphp
						<details class="relative hidden sm:block">
							<summary class="list-none cursor-pointer select-none rounded-xl px-3 py-2 text-sm font-semibold bg-white/10 hover:bg-white/15 inline-flex items-center gap-2">
								<span class="text-slate-100">Действия</span>
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

					{{-- Admin User dropdown (settings / server / logs / telegram users + logout) --}}
					<div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @click.outside="open = false">
						<button type="button" @click="open = true"
							class="inline-flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm transition hover:bg-white/5">
							<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white font-semibold">
								{{ strtoupper(substr((string) auth()->user()?->name, 0, 1)) }}
							</span>
							<span class="hidden sm:block font-medium text-slate-100">{{ auth()->user()?->name }}</span>
							<span class="text-[10px] text-slate-400" x-text="open ? '▲' : '▼'">▼</span>
						</button>

						<div x-show="open" x-cloak x-transition.origin.top.right
							class="absolute right-0 mt-2 w-60 rounded-2xl border border-white/10 bg-slate-900/95 backdrop-blur shadow-lg overflow-hidden z-50">
							<div class="p-2">
								@foreach($userMenuItems as $mi)
									@php
										$mr = (string) ($mi['route'] ?? '');
										$mhref = $mr === 'admin.server.errors' ? url('/admin/server') : (($mr !== '' && \Illuminate\Support\Facades\Route::has($mr)) ? route($mr) : null);
										$mactive = $mr === 'admin.server.errors' ? (request()->path() === 'admin/server') : ($mr !== '' && \Illuminate\Support\Facades\Route::has($mr) && request()->routeIs($mr));
									@endphp
									@if($mhref !== null)
										<a href="{{ $mhref }}"
											class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold {{ $mactive ? 'bg-white/10 text-white' : 'text-slate-200 hover:bg-white/10' }}">
											@if(!empty($mi['icon']))<x-admin.icon :name="$mi['icon']" class="h-4 w-4 text-slate-300" />@endif
											<span>{{ $mi['label'] ?? '' }}</span>
										</a>
									@endif
								@endforeach

								<div class="my-1 border-t border-white/10"></div>

								<a href="{{ route('profile.edit') }}"
									class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10">
									<span>Профиль</span>
								</a>

								@if(\Illuminate\Support\Facades\Route::has('logout'))
									<form method="POST" action="{{ route('logout') }}">
										@csrf
										<button type="submit"
											class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-semibold text-rose-300 hover:bg-rose-500/10">
											Выйти
										</button>
									</form>
								@endif
							</div>
						</div>
					</div>
				</div>
			</div>

			{{-- Mobile nav (same dropdowns; open on tap) --}}
			<div class="md:hidden pb-3">
				<div class="flex flex-wrap gap-1">
					@foreach($navItems as $item)
						<x-admin.nav-item :item="$item" />
					@endforeach
				</div>

				{{-- Mobile quick actions --}}
				@if(config('admin-kit.features.quick_actions') && count($qa) > 0)
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

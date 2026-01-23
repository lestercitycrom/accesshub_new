@props([
	'title' => null,
])

<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	@vite(['resources/css/app.css', 'resources/js/app.js'])
	@livewireStyles

	<title>{{ $title ?? __('Log in') }} — {{ config('admin-kit.brand.name', 'Admin') }}</title>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
	<div class="min-h-screen flex flex-col">
		<header class="border-b border-slate-200 bg-white">
			<div class="mx-auto max-w-7xl px-4">
				<div class="h-16 flex items-center justify-between">
					<a href="{{ url('/') }}" class="flex items-center gap-2">
						<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-white font-semibold">
							{{ config('admin-kit.brand.badge', 'AK') }}
						</span>
						<span class="font-semibold tracking-wide text-slate-900">
							{{ config('admin-kit.brand.name', 'Admin') }}
						</span>
					</a>

					<div class="text-xs text-slate-500 hidden sm:block">
						{{ __('Доступ к админ-панели') }}
					</div>
				</div>
			</div>
		</header>

		<main class="flex-1 flex items-center justify-center px-4 py-10">
			<div class="w-full max-w-md">
				{{ $slot }}
			</div>
		</main>

		<footer class="py-6 text-center text-xs text-slate-500">
			© {{ date('Y') }} {{ config('admin-kit.brand.name', 'Admin') }}
		</footer>
	</div>

	@livewireScripts
	@fluxScripts
</body>
</html>

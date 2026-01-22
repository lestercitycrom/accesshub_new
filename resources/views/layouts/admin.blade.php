<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	@livewireStyles
	<title>@yield('title', 'Admin')</title>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
	<div class="min-h-screen">
		<header class="border-b bg-white">
			<div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
				<div class="flex items-center gap-4">
					<a class="font-semibold" href="{{ route('admin.dashboard') }}">AccessHub Admin</a>
					<nav class="text-sm text-gray-700 flex gap-3">
						<a href="{{ route('admin.telegram-users.index') }}" class="hover:underline">Telegram Users</a>
						<a href="{{ route('admin.accounts.index') }}" class="hover:underline">Accounts</a>
						<a href="{{ route('admin.account-lookup') }}" class="hover:underline">Lookup</a>
						<a href="{{ route('admin.import.accounts') }}" class="hover:underline">Import</a>
						<a href="{{ route('admin.issuances.index') }}" class="hover:underline">Issuances</a>
						<a href="{{ route('admin.events.index') }}" class="hover:underline">Events</a>
					</nav>
				</div>

				<form method="POST" action="{{ route('logout') }}">
					@csrf
					<button class="text-sm text-gray-700 hover:underline" type="submit">Logout</button>
				</form>
			</div>
		</header>

		<main class="mx-auto max-w-7xl px-4 py-6">
			{{ $slot }}
		</main>
	</div>

	@livewireScripts
</body>
</html>
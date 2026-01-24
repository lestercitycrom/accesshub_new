<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	@vite(['resources/css/app.css', 'resources/js/app.js'])
	@livewireStyles

	<title>{{ $title ?? 'WebApp' }}</title>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
	{{ $slot }}

	@livewireScripts
</body>
</html>

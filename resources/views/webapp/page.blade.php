<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	@livewireStyles
	<title>AccessHub WebApp</title>
	<script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
	<div class="mx-auto max-w-3xl p-4 space-y-6">
		<header class="space-y-1">
			<h1 class="text-xl font-semibold">AccessHub WebApp</h1>
			<p class="text-sm text-gray-600">
				@if($this->isBootstrapped)
					Подключено как Telegram пользователь
				@else
					Необходимо выполнить bootstrap
				@endif
			</p>
		</header>

		@if(!$this->isBootstrapped)
			<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
				<div class="flex">
					<div class="ml-3">
						<p class="text-sm text-blue-700">
							Для работы в Telegram WebApp необходимо выполнить инициализацию.
							Нажмите кнопку ниже или откройте в Telegram.
						</p>
					</div>
				</div>
				<div class="mt-4">
					<button
						id="bootstrap-btn"
						class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
					>
						Подключиться к Telegram
					</button>
				</div>
			</div>
		@endif

		@if($this->isBootstrapped)
			<!-- Issue Form -->
			<div class="bg-white shadow rounded-lg p-6">
				<h2 class="text-lg font-medium text-gray-900 mb-4">Выдача аккаунта</h2>

				<form wire:submit="submit" class="space-y-4">
					<div>
						<label for="orderId" class="block text-sm font-medium text-gray-700">
							Номер заказа
						</label>
						<input
							wire:model="orderId"
							type="text"
							id="orderId"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
							required
						>
						@error('orderId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
					</div>

					<div>
						<label for="game" class="block text-sm font-medium text-gray-700">
							Игра
						</label>
						<select
							wire:model="game"
							id="game"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
							required
						>
							<option value="">Выберите игру</option>
							<option value="cs2">CS2</option>
							<option value="dota2">Dota 2</option>
							<option value="pubg">PUBG</option>
						</select>
						@error('game') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
					</div>

					<div>
						<label for="platform" class="block text-sm font-medium text-gray-700">
							Платформа
						</label>
						<select
							wire:model="platform"
							id="platform"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
							required
						>
							<option value="">Выберите платформу</option>
							<option value="steam">Steam</option>
							<option value="epic">Epic Games</option>
						</select>
						@error('platform') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
					</div>

					<div>
						<label for="qty" class="block text-sm font-medium text-gray-700">
							Количество
						</label>
						<input
							wire:model="qty"
							type="number"
							id="qty"
							min="1"
							max="2"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
							required
						>
						@error('qty') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
					</div>

					<button
						type="submit"
						class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
						wire:loading.attr="disabled"
						wire:loading.class="opacity-50 cursor-not-allowed"
					>
						<span wire:loading.remove>Выдать аккаунт</span>
						<span wire:loading>Обработка...</span>
					</button>
				</form>

				@if($resultText)
					<div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
						<pre class="text-sm text-green-800 whitespace-pre-wrap">{{ $resultText }}</pre>
					</div>
				@endif
			</div>

			<!-- History -->
			<div class="bg-white shadow rounded-lg p-6">
				<h2 class="text-lg font-medium text-gray-900 mb-4">История выдач</h2>

				@if($history->isEmpty())
					<p class="text-gray-500 text-center py-8">История пуста</p>
				@else
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
										Заказ
									</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
										Игра
									</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
										Кол-во
									</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
										Дата
									</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								@foreach($history as $issuance)
									<tr>
										<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
											{{ $issuance->order_id }}
										</td>
										<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
											{{ $issuance->game }} / {{ $issuance->platform }}
										</td>
										<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
											{{ $issuance->qty }}
										</td>
										<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
											{{ $issuance->issued_at->format('d.m.Y H:i') }}
										</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				@endif
			</div>
		@endif
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const bootstrapBtn = document.getElementById('bootstrap-btn');

			if (bootstrapBtn) {
				bootstrapBtn.addEventListener('click', function() {
					performBootstrap();
				});
			}

			// Auto-bootstrap if in Telegram WebApp
			if (window.Telegram && window.Telegram.WebApp) {
				performBootstrap();
			}
		});

		function performBootstrap() {
			if (!window.Telegram || !window.Telegram.WebApp) {
				alert('Telegram WebApp не доступен. Откройте в Telegram.');
				return;
			}

			const initData = window.Telegram.WebApp.initData;

			if (!initData) {
				alert('initData не доступна');
				return;
			}

			fetch('/webapp/bootstrap', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
				},
				body: JSON.stringify({
					initData: initData
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					location.reload();
				} else {
					alert('Ошибка bootstrap: ' + (data.error || 'Неизвестная ошибка'));
				}
			})
			.catch(error => {
				console.error('Bootstrap error:', error);
				alert('Ошибка подключения');
			});
		}
	</script>

	@livewireScripts
</body>
</html>
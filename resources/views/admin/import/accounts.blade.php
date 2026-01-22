<div class="space-y-6">
	<div class="flex flex-wrap items-start justify-between gap-3">
		<div>
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">Import Accounts</h1>
			<p class="text-sm text-slate-500">Загрузка текста CSV, предпросмотр и применение изменений.</p>
		</div>

		<div class="flex items-center gap-2">
			<x-admin.button variant="secondary" size="md" wire:click="reset(['csvText', 'preview', 'errors', 'showPreview'])">
				Reset
			</x-admin.button>
		</div>
	</div>

	@if(session('message'))
		<div class="rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
			{{ session('message') }}
		</div>
	@endif

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
		<x-admin.card title="Upload">
			<div class="space-y-4">
				<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
					<div class="text-sm font-semibold text-slate-900">Paste CSV text</div>
					<p class="text-xs text-slate-500 mt-1">CSV / TXT с заголовками. Рекомендуется CSV формат.</p>

					<div class="mt-3">
						<textarea
							class="w-full h-48 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
							placeholder="game,platform,login,password
cs2,steam,user1,password1
dota2,epic,user2,password2"
							wire:model="csvText"></textarea>
						@error('csvText') <div class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</div> @enderror
					</div>
				</div>

				@if($errors)
					<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
						<div class="font-semibold">Errors</div>
						<ul class="mt-2 list-disc pl-5 space-y-1">
							@foreach($errors as $error)
								<li class="text-xs">{{ $error }}</li>
							@endforeach
						</ul>
					</div>
				@endif

				<div class="flex items-center gap-2">
					<x-admin.button variant="primary" size="md" wire:click="parseCsv">
						Preview
					</x-admin.button>

					@if($showPreview)
						<x-admin.button variant="secondary" size="md" wire:click="applyImport">
							Apply
						</x-admin.button>
					@endif
				</div>

				<p class="text-xs text-slate-500">
					Preview не меняет БД. Apply применяет вставку новых аккаунтов.
				</p>
			</div>
		</x-admin.card>

		<x-admin.card title="Stats">
			<div class="grid grid-cols-1 gap-3 text-sm">
				<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
					<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Parsed</div>
					<div class="font-semibold text-slate-900">{{ $showPreview ? count($preview) : 0 }}</div>
				</div>

				<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
					<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Will create</div>
					<div class="font-semibold text-slate-900">{{ $showPreview ? count(array_filter($preview, fn($item) => !$item['exists'])) : 0 }}</div>
				</div>

				<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
					<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Will skip</div>
					<div class="font-semibold text-slate-900">{{ $showPreview ? count(array_filter($preview, fn($item) => $item['exists'])) : 0 }}</div>
				</div>

				<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
					<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Errors</div>
					<div class="font-semibold text-slate-900">{{ count($errors ?? []) }}</div>
				</div>
			</div>
		</x-admin.card>

		<x-admin.card title="Rules">
			<div class="space-y-2 text-sm text-slate-600">
				<p>Минимальные правила для импорта:</p>
				<ul class="list-disc pl-5 space-y-1">
					<li>login должен быть уникальным (в рамках game/platform)</li>
					<li>password обязателен</li>
					<li>status по умолчанию ACTIVE</li>
					<li>существующие аккаунты пропускаются</li>
				</ul>
			</div>
		</x-admin.card>
	</div>

	@if($showPreview)
		<x-admin.card title="Preview">
			<div class="overflow-x-auto rounded-2xl border border-slate-200">
				<table class="min-w-full text-sm">
					<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
						<tr>
							<th class="px-4 py-3 text-left">#</th>
							<th class="px-4 py-3 text-left">Game</th>
							<th class="px-4 py-3 text-left">Platform</th>
							<th class="px-4 py-3 text-left">Login</th>
							<th class="px-4 py-3 text-left">Password</th>
							<th class="px-4 py-3 text-left">Action</th>
						</tr>
					</thead>

					<tbody class="divide-y divide-slate-200 bg-white">
						@forelse($preview as $idx => $r)
							@php
								$action = $r['exists'] ? 'skip' : 'create';
								$variant = match($action) {
									'create' => 'green',
									'skip' => 'gray',
									default => 'gray',
								};
							@endphp

							<tr class="hover:bg-slate-50/70">
								<td class="px-4 py-3 text-slate-500">{{ $r['line'] }}</td>
								<td class="px-4 py-3">{{ $r['game'] }}</td>
								<td class="px-4 py-3">{{ $r['platform'] }}</td>
								<td class="px-4 py-3 font-semibold text-slate-900">{{ $r['login'] }}</td>
								<td class="px-4 py-3 text-slate-500">{{ !empty($r['password']) ? '••••••••' : '—' }}</td>
								<td class="px-4 py-3">
									<x-admin.badge :variant="$variant">{{ strtoupper($action) }}</x-admin.badge>
								</td>
							</tr>
						@empty
							<tr>
								<td class="px-4 py-10 text-center text-slate-500" colspan="6">
									Upload file and press Preview
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</x-admin.card>
	@endif
</div>
							<th class="py-2 pr-3">Game</th>
							<th class="py-2 pr-3">Platform</th>
							<th class="py-2 pr-3">Login</th>
							<th class="py-2 pr-3">Password</th>
							<th class="py-2 pr-3">Status</th>
						</tr>
					</thead>
					<tbody>
						@foreach($preview as $item)
							<tr class="border-b {{ $item['exists'] ? 'bg-yellow-50' : 'bg-green-50' }}">
								<td class="py-2 pr-3">{{ $item['line'] }}</td>
								<td class="py-2 pr-3">{{ $item['game'] }}</td>
								<td class="py-2 pr-3">{{ $item['platform'] }}</td>
								<td class="py-2 pr-3">{{ $item['login'] }}</td>
								<td class="py-2 pr-3">
									<span class="font-mono">{{ Str::limit($item['password'], 10) }}</span>
								</td>
								<td class="py-2 pr-3">
									@if($item['exists'])
										<span class="text-yellow-700">Already exists</span>
									@else
										<span class="text-green-700">Will be imported</span>
									@endif
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			<div class="flex items-center gap-2 pt-4">
				<button class="rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700" type="button" wire:click="applyImport">
					Apply Import
				</button>
				<button class="rounded-md border px-4 py-2" type="button" wire:click="$set('showPreview', false)">
					Cancel
				</button>
			</div>
		</div>
	@endif
</div>
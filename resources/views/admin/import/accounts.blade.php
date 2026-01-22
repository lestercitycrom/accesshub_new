<div class="space-y-6">
	<div class="flex flex-wrap items-start justify-between gap-3">
		<div>
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">Import Accounts</h1>
			<p class="text-sm text-slate-500">Загрузка файла → предпросмотр → применение. Всё без лишней сложности.</p>
		</div>

		<div class="flex items-center gap-2">
			<x-admin.button variant="secondary" size="md" wire:click="resetAll">
				Reset
			</x-admin.button>

			<a class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50"
				href="{{ route('admin.accounts.index') }}">
				Accounts
			</a>
		</div>
	</div>

	@if(session('message'))
		<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
			{{ session('message') }}
		</div>
	@endif

	@if(!empty($errors ?? null))
		<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
			<div class="font-semibold">Errors</div>
			<ul class="mt-2 list-disc pl-5 space-y-1">
				@foreach($errors as $error)
					<li class="text-xs">{{ $error }}</li>
				@endforeach
			</ul>
		</div>
	@endif

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
		<!-- Upload -->
		<x-admin.card title="Upload">
			<div class="space-y-4">
				<div class="rounded-2xl border border-slate-200 bg-white p-4">
					<div class="flex items-start gap-3">
						<div class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-semibold">
							CSV
						</div>

						<div class="min-w-0">
							<div class="text-sm font-semibold text-slate-900">Choose file</div>
							<div class="mt-1 text-xs text-slate-500">
								Рекомендуется CSV. Поддержка других форматов — если реализована в импорте.
							</div>
						</div>
					</div>

					<div class="mt-4">
						<input
							type="file"
							wire:model="file"
							class="block w-full text-sm text-slate-700 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
						/>
						@error('file')
							<div class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</div>
						@enderror
					</div>
				</div>

				<div class="flex flex-wrap items-center gap-2">
					<x-admin.button variant="primary" size="md" wire:click="preview">
						Preview
					</x-admin.button>

					<x-admin.button variant="secondary" size="md" wire:click="apply">
						Apply
					</x-admin.button>
				</div>

				<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
					<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Notes</div>
					<ul class="mt-2 list-disc pl-5 space-y-1 text-xs text-slate-500">
						<li><span class="font-semibold text-slate-700">Preview</span> не меняет БД.</li>
						<li><span class="font-semibold text-slate-700">Apply</span> применяет create/update.</li>
						<li>Лучше сначала прогнать на тестовой базе.</li>
					</ul>
				</div>
			</div>
		</x-admin.card>

		<!-- Stats -->
		<x-admin.card title="Stats">
			<div class="grid grid-cols-1 gap-3 text-sm">
				<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
					<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Parsed</div>
					<div class="font-semibold text-slate-900">{{ (int) ($stats['parsed'] ?? 0) }}</div>
				</div>

				<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
					<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Will create</div>
					<div class="font-semibold text-slate-900">{{ (int) ($stats['create'] ?? 0) }}</div>
				</div>

				<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
					<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Will update</div>
					<div class="font-semibold text-slate-900">{{ (int) ($stats['update'] ?? 0) }}</div>
				</div>

				<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
					<div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Skipped</div>
					<div class="font-semibold text-slate-900">{{ (int) ($stats['skipped'] ?? 0) }}</div>
				</div>

				@if(!empty($stats['errors'] ?? null))
					<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
						<div class="font-semibold">Errors</div>
						<ul class="mt-2 list-disc pl-5 space-y-1">
							@foreach((array) $stats['errors'] as $err)
								<li class="text-xs">{{ $err }}</li>
							@endforeach>
						</ul>
					</div>
				@endif
			</div>
		</x-admin.card>

		<!-- Rules -->
		<x-admin.card title="Rules">
			<div class="space-y-2 text-sm text-slate-600">
				<p class="font-semibold text-slate-900">Минимальные правила</p>
				<ul class="list-disc pl-5 space-y-1 text-xs text-slate-500">
					<li>login уникален внутри пары game/platform</li>
					<li>password обязателен</li>
					<li>status по умолчанию ACTIVE</li>
				</ul>
			</div>
		</x-admin.card>
	</div>

	<!-- Preview -->
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
						<th class="px-4 py-3 text-left">Reason</th>
					</tr>
				</thead>

				<tbody class="divide-y divide-slate-200 bg-white">
					@forelse(($previewRows ?? []) as $idx => $r)
						@php
							$action = (string) ($r['action'] ?? 'skip');
							$variant = match($action) {
								'create' => 'green',
								'update' => 'amber',
								default => 'gray',
							};
						@endphp

						<tr class="hover:bg-slate-50/70">
							<td class="px-4 py-3 text-slate-500">{{ $idx + 1 }}</td>
							<td class="px-4 py-3">{{ $r['game'] ?? '' }}</td>
							<td class="px-4 py-3">{{ $r['platform'] ?? '' }}</td>
							<td class="px-4 py-3 font-semibold text-slate-900">{{ $r['login'] ?? '' }}</td>
							<td class="px-4 py-3 text-slate-500">{{ !empty($r['password']) ? '••••••••' : '—' }}</td>
							<td class="px-4 py-3">
								<x-admin.badge :variant="$variant">{{ strtoupper($action) }}</x-admin.badge>
							</td>
							<td class="px-4 py-3 text-xs text-slate-500">
								{{ $r['reason'] ?? '—' }}
							</td>
						</tr>
					@empty
						<tr>
							<td class="px-4 py-10 text-center text-slate-500" colspan="7">
								Upload file and press Preview
							</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</x-admin.card>
</div>
<div class="space-y-6">
	<h1 class="text-xl font-semibold">Import Accounts</h1>

	@if(session('message'))
		<div class="rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
			{{ session('message') }}
		</div>
	@endif

	<div class="rounded-lg bg-white p-6 shadow-sm space-y-4">
		<div class="space-y-2">
			<label class="text-sm font-medium">CSV Text</label>
			<textarea
				class="w-full h-64 rounded-md border-gray-300 font-mono text-sm"
				placeholder="game,platform,login,password
cs2,steam,user1,password1
dota2,epic,user2,password2"
				wire:model="csvText"></textarea>
			<div class="text-xs text-gray-600">
				Format: game,platform,login,password (CSV with header row)
			</div>
		</div>

		@if($errors)
			<div class="rounded-md bg-red-50 p-4 border border-red-200">
				<h4 class="text-red-800 font-medium">Errors:</h4>
				<ul class="list-disc list-inside text-red-700 text-sm mt-2">
					@foreach($errors as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		@endif

		<div class="flex items-center gap-2">
			<button class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" type="button" wire:click="parseCsv">
				Preview Import
			</button>
		</div>
	</div>

	@if($showPreview)
		<div class="rounded-lg bg-white p-6 shadow-sm space-y-4">
			<h2 class="text-lg font-medium">Preview ({{ count($preview) }} accounts)</h2>

			<div class="overflow-x-auto">
				<table class="min-w-full text-sm">
					<thead>
						<tr class="text-left text-gray-600 border-b">
							<th class="py-2 pr-3">Line</th>
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
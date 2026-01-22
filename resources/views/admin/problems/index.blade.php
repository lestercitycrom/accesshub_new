<div class="space-y-6">
	<h1 class="text-xl font-semibold">Problem Accounts Management</h1>

	@if(session('message'))
		<div class="rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
			{{ session('message') }}
		</div>
	@endif

	<!-- Tabs -->
	<div class="border-b border-gray-200">
		<nav class="-mb-px flex space-x-8">
			@foreach($tabs as $tabName)
				<button
					wire:click="$set('tab', '{{ $tabName }}')"
					class="py-2 px-1 border-b-2 font-medium text-sm {{ $tab === $tabName ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
				>
					{{ $tabName }}
					@if($tabName !== 'ALL')
						<span class="ml-1 py-0.5 px-2 rounded-full text-xs {{ $tab === $tabName ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600' }}">
							{{ \App\Domain\Accounts\Models\Account::where('status', $tabName)->count() }}
						</span>
					@else
						<span class="ml-1 py-0.5 px-2 rounded-full text-xs {{ $tab === $tabName ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600' }}">
							{{ \App\Domain\Accounts\Models\Account::whereIn('status', ['STOLEN', 'RECOVERY', 'TEMP_HOLD', 'DEAD'])->count() }}
						</span>
					@endif
				</button>
			@endforeach
		</nav>
	</div>

	<!-- Search and Actions -->
	<div class="bg-white p-4 rounded-lg shadow-sm space-y-4">
		<div class="flex items-center justify-between">
			<div class="flex items-center space-x-4">
				<div>
					<label class="text-sm font-medium text-gray-700">Search by login</label>
					<input
						type="text"
						wire:model.live="q"
						placeholder="Enter login..."
						class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
					>
				</div>
			</div>

			@if(count($selected) > 0)
				<div class="flex items-center space-x-2">
					<span class="text-sm text-gray-600">{{ count($selected) }} selected</span>

					@if($tab === 'STOLEN' || $tab === 'ALL')
						<div class="flex items-center space-x-2">
							<input
								type="number"
								wire:model="extendDays"
								min="1"
								max="30"
								class="w-16 rounded-md border-gray-300 text-center"
							>
							<button
								wire:click="extendDeadline"
								class="px-3 py-2 bg-yellow-600 text-white text-sm rounded hover:bg-yellow-700"
							>
								Extend deadline +{{ $extendDays }}d
							</button>
						</div>
					@endif

					<button
						wire:click="releaseToPool"
						class="px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700"
					>
						Release to pool
					</button>

					<div class="flex space-x-1">
						@foreach(\App\Domain\Accounts\Enums\AccountStatus::cases() as $status)
							<button
								wire:click="setStatus('{{ $status->value }}')"
								class="px-3 py-2 bg-gray-600 text-white text-xs rounded hover:bg-gray-700"
							>
								Set {{ $status->value }}
							</button>
						@endforeach
					</div>
				</div>
			@endif
		</div>
	</div>

	<!-- Accounts Table -->
	<div class="bg-white rounded-lg shadow-sm overflow-hidden">
		@if($rows->count() > 0)
			<div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200">
					<thead class="bg-gray-50">
						<tr>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
								<input
									type="checkbox"
									class="rounded border-gray-300"
									wire:model="selected"
									wire:model.live
									@if(count($selected) > 0 && count($selected) === $rows->count()) checked @endif
								>
							</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Login</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Game/Platform</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flags</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
						</tr>
					</thead>
					<tbody class="bg-white divide-y divide-gray-200">
						@foreach($rows as $account)
							<tr class="{{ $account->status_deadline_at && $account->status_deadline_at->isPast() ? 'bg-red-50' : '' }}">
								<td class="px-6 py-4 whitespace-nowrap">
									<input
										type="checkbox"
										value="{{ $account->id }}"
										wire:model="selected"
										class="rounded border-gray-300"
									>
								</td>
								<td class="px-6 py-4 whitespace-nowrap">
									<div class="text-sm font-medium text-gray-900">{{ $account->login }}</div>
								</td>
								<td class="px-6 py-4 whitespace-nowrap">
									<div class="text-sm text-gray-900">{{ $account->game }}</div>
									<div class="text-sm text-gray-500">{{ $account->platform }}</div>
								</td>
								<td class="px-6 py-4 whitespace-nowrap">
									<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
										@if($account->status === \App\Domain\Accounts\Enums\AccountStatus::STOLEN) bg-red-100 text-red-800
										@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::RECOVERY) bg-yellow-100 text-yellow-800
										@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::TEMP_HOLD) bg-orange-100 text-orange-800
										@elseif($account->status === \App\Domain\Accounts\Enums\AccountStatus::DEAD) bg-gray-100 text-gray-800
										@else bg-green-100 text-green-800 @endif">
										{{ $account->status->value }}
									</span>
								</td>
								<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
									{{ $account->assigned_to_telegram_id ?: '-' }}
								</td>
								<td class="px-6 py-4 whitespace-nowrap text-sm">
									@if($account->status_deadline_at)
										<div class="{{ $account->status_deadline_at->isPast() ? 'text-red-600 font-medium' : 'text-gray-900' }}">
											{{ $account->status_deadline_at->format('d.m.Y H:i') }}
										</div>
										@if(!$account->status_deadline_at->isPast())
											<div class="text-xs text-gray-500">
												{{ $account->status_deadline_at->diffInDays() }} days left
											</div>
										@endif
									@else
										-
									@endif
								</td>
								<td class="px-6 py-4 whitespace-nowrap">
									@if($account->flags)
										<div class="flex flex-wrap gap-1">
											@foreach($account->flags as $key => $value)
												<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
													{{ $key }}
												</span>
											@endforeach
										</div>
									@else
										-
									@endif
								</td>
								<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
									<a href="{{ route('admin.accounts.show', $account) }}" class="text-blue-600 hover:text-blue-900">
										View Details
									</a>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		@else
			<div class="text-center py-12">
				<div class="text-gray-500 text-lg">No problem accounts found</div>
				<div class="text-gray-400 text-sm mt-2">
					@if($tab === 'ALL')
						All accounts are in good standing!
					@else
						No accounts with status "{{ $tab }}"
					@endif
				</div>
			</div>
		@endif
	</div>
</div>
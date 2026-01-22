<div class="space-y-6">
	<div class="flex flex-wrap items-center justify-between gap-3">
		<div>
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">Telegram Users</h1>
			<p class="text-sm text-slate-500">Управление операторами/админами и их активностью.</p>
		</div>

		<div class="flex items-center gap-2">
			<x-admin.button variant="secondary" size="md" onclick="window.location='{{ route('admin.telegram-users.index') }}'">
				Refresh
			</x-admin.button>

			<x-admin.button variant="primary" size="md" onclick="window.location='{{ route('admin.telegram-users.create') }}'">
				Create
			</x-admin.button>
		</div>
	</div>

	<x-admin.card title="Users" :actions="null">
		<div class="space-y-4">
			<div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
				<div class="w-full lg:max-w-md">
					<x-admin.input
						label="Search"
						placeholder="Search by username / name / telegram id..."
						wire:model.live="q"
					/>
				</div>

				<div class="flex flex-wrap items-center gap-2">
					<x-admin.button variant="secondary" size="sm" wire:click="toggleActive(true)">
						Activate
					</x-admin.button>

					<x-admin.button variant="secondary" size="sm" wire:click="toggleActive(false)">
						Deactivate
					</x-admin.button>

					<x-admin.button variant="ghost" size="sm" wire:click="setRole('operator')">
						Role: operator
					</x-admin.button>

					<x-admin.button variant="ghost" size="sm" wire:click="setRole('admin')">
						Role: admin
					</x-admin.button>

					<div class="ml-2 text-xs text-slate-500">
						Selected: <span class="font-semibold text-slate-700">{{ is_array($selected ?? null) ? count($selected) : 0 }}</span>
					</div>
				</div>
			</div>

			<div class="overflow-x-auto rounded-2xl border border-slate-200">
				<table class="min-w-full text-sm">
					<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
						<tr>
							<th class="w-10 px-4 py-3">
								<input type="checkbox" class="rounded border-slate-300"
									@if(count($selected ?? []) && count($selected) === $rows->count()) checked @endif
									wire:click="$set('selected', {{ $rows->pluck('id') }})">
							</th>
							<th class="px-4 py-3 text-left">Telegram ID</th>
							<th class="px-4 py-3 text-left">User</th>
							<th class="px-4 py-3 text-left">Role</th>
							<th class="px-4 py-3 text-left">Active</th>
							<th class="px-4 py-3 text-right">Action</th>
						</tr>
					</thead>

					<tbody class="divide-y divide-slate-200 bg-white">
						@forelse($rows as $row)
							<tr class="hover:bg-slate-50/70">
								<td class="px-4 py-3">
									<input type="checkbox" value="{{ $row->id }}" wire:model="selected" class="rounded border-slate-300">
								</td>

								<td class="px-4 py-3 font-semibold text-slate-900">
									{{ $row->telegram_id }}
								</td>

								<td class="px-4 py-3">
									<div class="font-semibold text-slate-900">
										{{ $row->username ?? '-' }}
									</div>
									<div class="text-xs text-slate-500">
										{{ trim(($row->first_name ?? '').' '.($row->last_name ?? '')) ?: '-' }}
									</div>
								</td>

								<td class="px-4 py-3">
									<x-admin.badge :variant="($row->role->value ?? '') === 'admin' ? 'violet' : 'blue'">
										{{ $row->role->value ?? 'operator' }}
									</x-admin.badge>
								</td>

								<td class="px-4 py-3">
									@if((bool) ($row->is_active ?? false))
										<x-admin.badge variant="green">yes</x-admin.badge>
									@else
										<x-admin.badge variant="red">no</x-admin.badge>
									@endif
								</td>

								<td class="px-4 py-3 text-right">
									<a class="text-sm font-semibold text-slate-900 hover:text-slate-700 underline"
										href="{{ route('admin.telegram-users.edit', $row) }}">
										Edit
									</a>
								</td>
							</tr>
						@empty
							<tr>
								<td class="px-4 py-10 text-center text-slate-500" colspan="6">
									No users found
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>

			@if(method_exists($rows, 'links'))
				<div class="pt-2">
					{{ $rows->links() }}
				</div>
			@endif
		</div>
	</x-admin.card>
</div>
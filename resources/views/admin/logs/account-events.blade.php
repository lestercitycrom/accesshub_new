<div class="space-y-4">
	<div class="flex items-center justify-between">
		<h1 class="text-xl font-semibold">Account Events Log</h1>
		<button class="rounded-md border px-4 py-2" type="button" wire:click="clearFilters">Clear</button>
	</div>

	<div class="rounded-lg bg-white p-4 shadow-sm space-y-3">
		<div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
			<input class="w-full rounded-md border-gray-300" type="text" placeholder="Account ID" wire:model.live="accountId">
			<input class="w-full rounded-md border-gray-300" type="text" placeholder="Telegram ID" wire:model.live="telegramId">
			<input class="w-full rounded-md border-gray-300" type="text" placeholder="Type (SET_STATUS...)" wire:model.live="type">
			<input class="w-full rounded-md border-gray-300" type="date" wire:model.live="dateFrom">
			<input class="w-full rounded-md border-gray-300" type="date" wire:model.live="dateTo">
		</div>

		<div class="overflow-x-auto">
			<table class="min-w-full text-sm">
				<thead>
					<tr class="text-left text-gray-600">
						<th class="py-2 pr-3">At</th>
						<th class="py-2 pr-3">Account</th>
						<th class="py-2 pr-3">Type</th>
						<th class="py-2 pr-3">Actor telegram_id</th>
						<th class="py-2 pr-3">Payload</th>
					</tr>
				</thead>
				<tbody>
					@forelse($rows as $r)
						<tr class="border-t">
							<td class="py-2 pr-3">{{ $r->created_at?->format('Y-m-d H:i') }}</td>
							<td class="py-2 pr-3">
								<a class="underline" href="{{ route('admin.accounts.show', $r->account_id) }}">#{{ $r->account_id }}</a>
							</td>
							<td class="py-2 pr-3">{{ $r->type }}</td>
							<td class="py-2 pr-3">{{ $r->telegram_id ?? '-' }}</td>
							<td class="py-2 pr-3">
								<pre class="text-xs whitespace-pre-wrap">@json($r->payload)</pre>
							</td>
						</tr>
					@empty
						<tr class="border-t">
							<td class="py-3 text-gray-500" colspan="5">No rows</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		{{ $rows->links() }}
	</div>
</div>
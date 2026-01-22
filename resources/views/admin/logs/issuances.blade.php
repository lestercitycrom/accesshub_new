<div class="space-y-4">
	<div class="flex items-center justify-between">
		<h1 class="text-xl font-semibold">Issuances Log</h1>
		<button class="rounded-md border px-4 py-2" type="button" wire:click="clearFilters">Clear</button>
	</div>

	<div class="rounded-lg bg-white p-4 shadow-sm space-y-3">
		<div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
			<input class="w-full rounded-md border-gray-300" type="text" placeholder="Order ID" wire:model.live="orderId">
			<input class="w-full rounded-md border-gray-300" type="text" placeholder="Telegram ID" wire:model.live="telegramId">
			<input class="w-full rounded-md border-gray-300" type="text" placeholder="Account ID" wire:model.live="accountId">
			<input class="w-full rounded-md border-gray-300" type="text" placeholder="Game" wire:model.live="game">
			<input class="w-full rounded-md border-gray-300" type="text" placeholder="Platform" wire:model.live="platform">
			<div class="grid grid-cols-2 gap-2">
				<input class="w-full rounded-md border-gray-300" type="date" wire:model.live="dateFrom">
				<input class="w-full rounded-md border-gray-300" type="date" wire:model.live="dateTo">
			</div>
		</div>

		<div class="overflow-x-auto">
			<table class="min-w-full text-sm">
				<thead>
					<tr class="text-left text-gray-600">
						<th class="py-2 pr-3">Issued</th>
						<th class="py-2 pr-3">Order</th>
						<th class="py-2 pr-3">Account</th>
						<th class="py-2 pr-3">Operator</th>
						<th class="py-2 pr-3">Game</th>
						<th class="py-2 pr-3">Platform</th>
						<th class="py-2 pr-3">Qty</th>
						<th class="py-2 pr-3">Cooldown</th>
					</tr>
				</thead>
				<tbody>
					@forelse($rows as $r)
						<tr class="border-t">
							<td class="py-2 pr-3">{{ $r->issued_at?->format('Y-m-d H:i') }}</td>
							<td class="py-2 pr-3">{{ $r->order_id }}</td>
							<td class="py-2 pr-3">
								<a class="underline" href="{{ route('admin.accounts.show', $r->account_id) }}">
									#{{ $r->account_id }}
								</a>
							</td>
							<td class="py-2 pr-3">
								<div class="text-xs text-gray-500">{{ $r->telegram_id }}</div>
								<div>{{ $r->telegramUser?->username ?? '-' }}</div>
							</td>
							<td class="py-2 pr-3">{{ $r->game }}</td>
							<td class="py-2 pr-3">{{ $r->platform }}</td>
							<td class="py-2 pr-3">{{ $r->qty }}</td>
							<td class="py-2 pr-3">{{ $r->cooldown_until?->format('Y-m-d') ?? '-' }}</td>
						</tr>
					@empty
						<tr class="border-t">
							<td class="py-3 text-gray-500" colspan="8">No rows</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		{{ $rows->links() }}
	</div>
</div>
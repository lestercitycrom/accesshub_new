<div class="space-y-6">
	<x-admin.page-header
		title="Delivery Links"
		subtitle="Generate unique single-use links to upload as marketplace stock keys (difmark). Each link opens the take-order form once."
	>
		<x-admin.page-actions>
			<x-admin.button variant="secondary" size="sm" href="{{ route('admin.delivery-orders.index') }}">
				Delivery orders
			</x-admin.button>
		</x-admin.page-actions>
	</x-admin.page-header>

	@if(session('message'))
		<x-admin.alert variant="success" :message="session('message')" />
	@endif

	<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
		<x-admin.card title="Generate links">
			<div class="space-y-4">
				<x-admin.input
					label="How many links"
					type="number"
					min="1"
					max="{{ \App\Admin\Livewire\DeliveryLinks\DeliveryLinksIndex::MAX_PER_BATCH }}"
					wire:model="count"
					:error="$errors->first('count')"
				/>

				<x-admin.input
					label="Game"
					type="text"
					list="delivery-link-game-options"
					placeholder="e.g. Alan Wake 2"
					wire:model="game"
					:error="$errors->first('game')"
				/>
				<datalist id="delivery-link-game-options">
					@foreach($gameOptions as $gameOption)
						<option value="{{ $gameOption }}"></option>
					@endforeach
				</datalist>

				<x-admin.input
					label="Note (optional)"
					type="text"
					placeholder="e.g. offer EA FC 25 PS5"
					wire:model="note"
					:error="$errors->first('note')"
				/>

				<x-admin.button variant="primary" wire:click="generate" wire:loading.attr="disabled">
					<span wire:loading.remove wire:target="generate">Generate</span>
					<span wire:loading wire:target="generate">Generating...</span>
				</x-admin.button>

				<p class="text-xs text-slate-500">
					Each generation creates a batch for one game. Up to
					{{ number_format(\App\Admin\Livewire\DeliveryLinks\DeliveryLinksIndex::MAX_PER_BATCH) }}
					links per batch. Then export the batch as CSV and upload to the offer.
				</p>
			</div>
		</x-admin.card>

		<div class="lg:col-span-2 space-y-6">
			<x-admin.card title="Totals">
				<div class="grid grid-cols-3 gap-3 text-center">
					<div class="rounded-xl border border-slate-200 bg-white p-3">
						<div class="text-2xl font-semibold text-slate-900">{{ number_format($totals->total) }}</div>
						<div class="text-xs text-slate-500">Total</div>
					</div>
					<div class="rounded-xl border border-slate-200 bg-white p-3">
						<div class="text-2xl font-semibold text-emerald-600">{{ number_format($totals->unused) }}</div>
						<div class="text-xs text-slate-500">Unused</div>
					</div>
					<div class="rounded-xl border border-slate-200 bg-white p-3">
						<div class="text-2xl font-semibold text-slate-400">{{ number_format($totals->used) }}</div>
						<div class="text-xs text-slate-500">Used</div>
					</div>
				</div>

				<div class="mt-4 flex flex-wrap gap-2">
					<x-admin.button variant="secondary" size="sm" :href="route('admin.export.delivery-links.csv', ['only' => 'unused'])">
						Export all unused (CSV)
					</x-admin.button>
				</div>
			</x-admin.card>

			<x-admin.card title="Batches">
				<div class="overflow-x-auto">
					<table class="w-full text-sm">
						<thead>
							<tr class="text-left text-xs uppercase tracking-wide text-slate-400">
								<th class="px-3 py-2">Batch</th>
								<th class="px-3 py-2">Game</th>
								<th class="px-3 py-2">Created</th>
								<th class="px-3 py-2 text-right">Total</th>
								<th class="px-3 py-2 text-right">Unused</th>
								<th class="px-3 py-2 text-right">Used</th>
								<th class="px-3 py-2 text-right">Actions</th>
							</tr>
						</thead>
						<tbody>
							@forelse($batches as $row)
								<tr class="border-t border-slate-100">
									<td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $row->batch ?? '-' }}</td>
									<td class="px-3 py-2">
										<div class="max-w-56 truncate font-semibold text-slate-900" title="{{ $row->game ?: '-' }}">
											{{ $row->game ?: '-' }}
										</div>
									</td>
									<td class="px-3 py-2 text-slate-500">{{ $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('Y-m-d H:i') : '-' }}</td>
									<td class="px-3 py-2 text-right">{{ number_format($row->total) }}</td>
									<td class="px-3 py-2 text-right text-emerald-600">{{ number_format($row->unused) }}</td>
									<td class="px-3 py-2 text-right text-slate-400">{{ number_format($row->used) }}</td>
									<td class="px-3 py-2">
										<div class="flex flex-wrap justify-end gap-2">
											@if($row->batch !== null)
											<x-admin.button variant="secondary" size="sm" :href="route('admin.export.delivery-links.csv', ['batch' => $row->batch, 'only' => 'unused'])">
													CSV (unused)
												</x-admin.button>
											<x-admin.button variant="ghost" size="sm" :href="route('admin.export.delivery-links.csv', ['batch' => $row->batch, 'only' => 'all'])">
													CSV (all)
												</x-admin.button>
												@can('hub-supply')
												@if($row->unused > 0)
													<x-admin.button
														variant="ghost"
														size="sm"
														wire:click="deleteUnused(@js($row->batch))"
														wire:confirm="Remove {{ $row->unused }} unused links from this batch?"
													>
														Delete unused
													</x-admin.button>
												@endif
												@endcan
											@endif
										</div>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="7" class="px-3 py-6 text-center text-slate-500">
										No links yet. Generate a batch to get started.
									</td>
								</tr>
							@endforelse
						</tbody>
					</table>
				</div>
			</x-admin.card>
		</div>
	</div>
</div>

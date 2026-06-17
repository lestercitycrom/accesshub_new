<div class="space-y-6">
	<x-admin.page-header
		title="Delivery Instructions"
		subtitle="Editable customer instructions shown on the public delivery page for each platform."
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
		<x-admin.card title="Platforms">
			<div class="space-y-2">
				@forelse($instructions as $instruction)
					<div class="rounded-xl border {{ $platform === $instruction->platform ? 'border-slate-400 bg-slate-50' : 'border-slate-200 bg-white' }} p-3">
						<div class="flex items-start justify-between gap-3">
							<div>
								<div class="font-semibold text-slate-900">{{ $instruction->platform }}</div>
								<div class="mt-1 text-xs text-slate-500">{{ str($instruction->title)->limit(44) }}</div>
							</div>

							<x-admin.badge :variant="$instruction->is_active ? 'green' : 'gray'">
								{{ $instruction->is_active ? 'active' : 'hidden' }}
							</x-admin.badge>
						</div>

						<div class="mt-3 flex flex-wrap gap-2">
							<x-admin.button
								variant="{{ $platform === $instruction->platform ? 'primary' : 'secondary' }}"
								size="sm"
								wire:click="selectPlatform(@js($instruction->platform))"
							>
								Edit
							</x-admin.button>

							<x-admin.button
								variant="ghost"
								size="sm"
								wire:click="toggleActive(@js($instruction->platform))"
							>
								{{ $instruction->is_active ? 'Hide' : 'Show' }}
							</x-admin.button>
						</div>
					</div>
				@empty
					<div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
						No instructions found. Select a platform and save the first instruction.
					</div>
				@endforelse
			</div>
		</x-admin.card>

		<div class="lg:col-span-2 space-y-6">
			<x-admin.card title="Instruction editor">
				<div class="space-y-4">
					<x-admin.select
						label="Platform"
						wire:model.live="platform"
						wire:change="selectPlatform($event.target.value)"
						:error="$errors->first('platform')"
					>
						@foreach($platformOptions as $option)
							<option value="{{ $option }}">{{ $option }}</option>
						@endforeach
					</x-admin.select>

					<x-admin.input
						label="Title"
						type="text"
						wire:model="title"
						:error="$errors->first('title')"
					/>

					<div class="space-y-1">
						<label class="text-xs font-semibold text-slate-700">Body</label>
						<textarea
							wire:model="body"
							rows="8"
							class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
							placeholder="Customer-facing instruction text"
						></textarea>
						@if($errors->first('body'))
							<div class="text-xs font-medium text-rose-600">{{ $errors->first('body') }}</div>
						@endif
					</div>

					<label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
						<input
							type="checkbox"
							wire:model="isActive"
							class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-300"
						>
						Show this instruction on public order pages
					</label>

					<div class="flex flex-wrap items-center gap-2">
						<x-admin.button variant="primary" wire:click="save">
							Save instruction
						</x-admin.button>

						<x-admin.button variant="secondary" wire:click="selectPlatform(@js($platform))">
							Reset form
						</x-admin.button>
					</div>
				</div>
			</x-admin.card>

			<x-admin.card title="Public preview">
				<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
					<div class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $platform }}</div>
					<div class="mt-2 text-lg font-semibold text-slate-900">{{ $title }}</div>
					<div class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-600">{{ $body ?: 'Instruction body is empty.' }}</div>
				</div>
			</x-admin.card>
		</div>
	</div>
</div>

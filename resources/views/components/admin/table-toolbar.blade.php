@props([
	'title' => null,
	'density' => null, // normal|compact
	'showDensity' => false, // whether to show density toggle
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-3']) }}>
	@if($title)
		<div class="text-sm font-semibold text-slate-900">{{ $title }}</div>
	@endif

	<div class="flex flex-wrap items-center gap-2">
		{{ $slot }}

		@if($density && $showDensity)
			<div class="inline-flex rounded-xl bg-white">
				<button type="button"
					wire:click="$set('density', 'normal')"
					class="rounded-l-xl px-3 py-2 text-xs font-semibold border border-slate-200 {{ $density === 'normal' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
					Normal
				</button>
				<button type="button"
					wire:click="$set('density', 'compact')"
					class="rounded-r-xl px-3 py-2 text-xs font-semibold border-y border-r border-slate-200 {{ $density === 'compact' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
					Compact
				</button>
			</div>
		@endif
	</div>
</div>
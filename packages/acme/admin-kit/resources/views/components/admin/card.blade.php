@props([
	'title' => null,
	'actions' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white border border-slate-200 shadow-sm']) }}>
	@if($title || $actions)
		<div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-200">
			<div class="min-w-0">
				@if($title)
					<div class="text-sm font-semibold text-slate-900 truncate">{{ $title }}</div>
				@endif
			</div>

			@if($actions)
				<div class="flex items-center gap-2">
					{{ $actions }}
				</div>
			@endif
		</div>
	@endif

	<div class="p-5">
		{{ $slot }}
	</div>
</div>
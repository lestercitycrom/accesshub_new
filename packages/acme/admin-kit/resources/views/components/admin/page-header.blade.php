@props([
	'title',
	'subtitle' => null,
	'meta' => null, // string|html
])

<div {{ $attributes->merge(['class' => 'space-y-3']) }}>
	<div class="flex flex-wrap items-start justify-between gap-3">
		<div class="min-w-0">
			<h1 class="text-2xl font-semibold tracking-tight text-slate-900">
				{{ $title }}
			</h1>

			@if($subtitle)
				<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
			@endif

			@if($meta)
				<div class="mt-2 text-xs text-slate-500">
					{!! $meta !!}
				</div>
			@endif
		</div>

		@if(trim($slot) !== '')
			<div class="flex flex-wrap items-center gap-2">
				{{ $slot }}
			</div>
		@endif
	</div>

	@if(isset($breadcrumbs))
		<div class="text-xs text-slate-500">
			{{ $breadcrumbs }}
		</div>
	@endif
</div>
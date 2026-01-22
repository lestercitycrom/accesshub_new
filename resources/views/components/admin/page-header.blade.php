@props([
	'title',
	'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-3']) }}>
	<div class="min-w-0">
		<h1 class="text-2xl font-semibold tracking-tight text-slate-900">
			{{ $title }}
		</h1>

		@if($subtitle)
			<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
		@endif
	</div>

	@if(trim($slot) !== '')
		<div class="flex flex-wrap items-center gap-2">
			{{ $slot }}
		</div>
	@endif
</div>
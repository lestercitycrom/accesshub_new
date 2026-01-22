@props([
	'primaryLabel' => null,
	'primaryHref' => null,
	'primaryIcon' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
	@if($primaryLabel && $primaryHref)
		<a
			href="{{ $primaryHref }}"
			class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800 gap-2"
		>
			@if($primaryIcon)
				<x-admin.icon :name="$primaryIcon" class="h-4 w-4" />
			@endif
			<span>{{ $primaryLabel }}</span>
		</a>
	@endif

	@if(trim($slot) !== '')
		{{ $slot }}
	@endif
</div>
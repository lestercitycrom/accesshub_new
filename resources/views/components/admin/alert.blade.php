@props([
	'variant' => 'success', // success|warning|danger|info
	'title' => null,
	'message' => null,
])

@php
	$classes = match ($variant) {
		'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
		'danger' => 'border-rose-200 bg-rose-50 text-rose-900',
		'info' => 'border-sky-200 bg-sky-50 text-sky-900',
		default => 'border-emerald-200 bg-emerald-50 text-emerald-900',
	};
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border p-4 text-sm '.$classes]) }}>
	@if($title)
		<div class="font-semibold">{{ $title }}</div>
	@endif

	@if($message)
		<div class="{{ $title ? 'mt-1' : '' }}">{{ $message }}</div>
	@else
		{{ $slot }}
	@endif
</div>
@props([
	'variant' => 'primary', // primary|secondary|danger|ghost
	'size' => 'md', // sm|md
	'type' => 'button',
])

@php
	$base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition select-none focus:outline-none focus:ring-2 focus:ring-offset-2';
	$sizeClass = $size === 'sm'
		? 'px-3 py-2 text-xs'
		: 'px-4 py-2.5 text-sm';

	$variantClass = match ($variant) {
		'secondary' => 'bg-white text-slate-900 border border-slate-200 hover:bg-slate-50 focus:ring-slate-300',
		'danger' => 'bg-rose-600 text-white hover:bg-rose-500 focus:ring-rose-300',
		'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100 focus:ring-slate-200',
		default => 'bg-slate-900 text-white hover:bg-slate-800 focus:ring-slate-300',
	};
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $base.' '.$sizeClass.' '.$variantClass]) }}>
	{{ $slot }}
</button>
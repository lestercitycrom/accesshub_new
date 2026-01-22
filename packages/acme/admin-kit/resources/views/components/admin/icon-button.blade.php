@props([
	'href' => null,
	'icon' => 'eye',
	'title' => null,
	'variant' => 'secondary', // secondary|primary|danger|ghost
])

@php
	$base = 'inline-flex items-center justify-center rounded-xl h-9 w-9 transition focus:outline-none focus:ring-2 focus:ring-offset-2';

	$variantClass = match ($variant) {
		'primary' => 'bg-slate-900 text-white hover:bg-slate-800 focus:ring-slate-300',
		'danger' => 'bg-rose-600 text-white hover:bg-rose-500 focus:ring-rose-300',
		'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100 focus:ring-slate-200',
		default => 'bg-white text-slate-900 border border-slate-200 hover:bg-slate-50 focus:ring-slate-300',
	};
@endphp

@if($href)
	<a href="{{ $href }}" title="{{ $title }}" aria-label="{{ $title }}" {{ $attributes->merge(['class' => $base.' '.$variantClass]) }}>
		<x-admin.icon :name="$icon" class="h-4 w-4" />
	</a>
@else
	<button type="button" title="{{ $title }}" aria-label="{{ $title }}" {{ $attributes->merge(['class' => $base.' '.$variantClass]) }}>
		<x-admin.icon :name="$icon" class="h-4 w-4" />
	</button>
@endif
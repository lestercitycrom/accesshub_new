@aware(['density'])

@props([
	'align' => 'left', // left|right|center
	'muted' => false,
	'nowrap' => false,
])

@php
	$d = (string) ($density ?? 'normal');
	$pad = $d === 'compact' ? 'px-4 py-2.5' : 'px-4 py-3';

	$alignClass = match ($align) {
		'right' => 'text-right',
		'center' => 'text-center',
		default => 'text-left',
	};

	$mutedClass = $muted ? 'text-slate-500' : '';
	$nowrapClass = $nowrap ? 'whitespace-nowrap' : '';
@endphp

<td {{ $attributes->merge(['class' => $pad.' '.$alignClass.' '.$mutedClass.' '.$nowrapClass]) }}>
	{{ $slot }}
</td>
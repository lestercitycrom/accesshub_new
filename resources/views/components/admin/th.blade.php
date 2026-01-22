@aware(['density'])

@props([
	'align' => 'left', // left|right|center
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

	$nowrapClass = $nowrap ? 'whitespace-nowrap' : '';
@endphp

<th {{ $attributes->merge(['class' => $pad.' '.$alignClass.' '.$nowrapClass]) }}>
	{{ $slot }}
</th>
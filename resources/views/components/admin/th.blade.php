@aware(['density'])

@props([
	'align' => 'left', // left|right|center
	'nowrap' => false,
	'sortable' => false,
	'sorted' => false,
	'direction' => null, // 'asc'|'desc'|null
	'sortField' => null, // field name for sorting
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
	
	$sortableClass = $sortable ? 'cursor-pointer select-none hover:bg-slate-100 transition-colors' : '';
	$sortIcon = '';
	if ($sortable && $sorted) {
		$sortIcon = $direction === 'asc' 
			? '<svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>'
			: '<svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
	} elseif ($sortable) {
		$sortIcon = '<svg class="inline-block w-4 h-4 ml-1 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>';
	}
@endphp

<th 
	{{ $attributes->merge(['class' => $pad.' '.$alignClass.' '.$nowrapClass.' '.$sortableClass]) }}
	@if($sortable && $sortField)
		wire:click="sort('{{ $sortField }}')"
	@endif
>
	<div class="flex items-center {{ $align === 'right' ? 'justify-end' : ($align === 'center' ? 'justify-center' : 'justify-start') }}">
		<span>{{ $slot }}</span>
		{!! $sortIcon !!}
	</div>
</th>
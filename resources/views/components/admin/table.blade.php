@props([
	'density' => 'normal', // normal|compact
	'sticky' => false,
	'zebra' => false,
])

@php
	$theadClass = $sticky ? 'sticky top-0 z-10' : '';
	$tbodyClass = $zebra ? '[&>tr:nth-child(even)]:bg-slate-50/30' : '';
@endphp

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-2xl border border-slate-200']) }}>
	<table class="min-w-full text-sm">
		<thead class="{{ $theadClass }} bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600 border-b border-slate-200">
			{{ $head ?? '' }}
		</thead>

		<tbody class="divide-y divide-slate-200 {{ $tbodyClass }}">
			{{ $slot }}
		</tbody>
	</table>
</div>
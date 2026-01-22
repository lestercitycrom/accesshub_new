@props([
	'density' => 'normal', // normal|compact
	'sticky' => false,
	'zebra' => false,
])

@php
	$theadClass = $sticky ? 'sticky top-0 z-10' : '';
	$tbodyClass = $zebra ? 'divide-y divide-slate-200' : 'divide-y divide-slate-200';
@endphp

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-2xl border border-slate-200']) }}>
	<table class="min-w-full text-sm">
		<thead class="{{ $theadClass }} bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600 border-b border-slate-200">
			{{ $head ?? '' }}
		</thead>

		<tbody class="{{ $tbodyClass }}">
			@php $rowIndex = 0; @endphp
			@foreach($slot->toArray() as $row)
				@php $rowIndex++; @endphp
				<tr class="hover:bg-slate-50/70 {{ $zebra && $rowIndex % 2 === 0 ? 'bg-slate-50/30' : '' }}">
					{{ $row }}
				</tr>
			@endforeach
		</tbody>
	</table>
</div>
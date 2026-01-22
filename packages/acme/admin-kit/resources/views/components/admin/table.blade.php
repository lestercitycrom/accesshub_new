@props([
	'density' => 'normal', // normal|compact
	'sticky' => false,
])

@php
	$theadClass = $sticky ? 'sticky top-0 z-10' : '';
@endphp

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-2xl border border-slate-200']) }}>
	<table class="min-w-full text-sm">
		<thead class="{{ $theadClass }} bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
			{{ $head ?? '' }}
		</thead>

		<tbody class="divide-y divide-slate-200 bg-white">
			{{ $slot }}
		</tbody>
	</table>
</div>
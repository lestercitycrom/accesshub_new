@props(['label'])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5']) }}>
	<div class="text-xs font-medium text-slate-400 mb-0.5">{{ $label }}</div>
	<div class="text-sm font-semibold text-slate-900 break-all">{{ $slot }}</div>
</div>

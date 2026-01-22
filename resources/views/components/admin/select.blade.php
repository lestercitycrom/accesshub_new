@props([
	'label' => null,
	'hint' => null,
	'error' => null,
])

<div class="space-y-1">
	@if($label)
		<label class="text-xs font-semibold text-slate-700">{{ $label }}</label>
	@endif

	<select {{ $attributes->merge(['class' => 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200']) }}>
		{{ $slot }}
	</select>

	@if($hint)
		<div class="text-xs text-slate-500">{{ $hint }}</div>
	@endif

	@if($error)
		<div class="text-xs font-medium text-rose-600">{{ $error }}</div>
	@endif
</div>
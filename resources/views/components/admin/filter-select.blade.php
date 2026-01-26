@props([
	'label' => null,
	'icon' => null,
])

<div class="space-y-1">
	@if($label)
		<div class="text-[11px] font-semibold text-slate-600">{{ $label }}</div>
	@endif

	<div class="relative">
		@if($icon)
			<span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
				<x-admin.icon :name="$icon" class="h-4 w-4" />
			</span>
		@endif

		<select
			{{ $attributes->merge([
				'class' => 'w-full appearance-none rounded-xl border border-slate-200 bg-white/70 px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300 '.($icon ? 'pl-10' : ''),
			]) }}
		>
			{{ $slot }}
		</select>

		<span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
			v
		</span>
	</div>
</div>

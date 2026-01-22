@props([
	'title' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
	@if($title)
		<div class="px-5 py-4 border-b border-slate-200">
			<div class="text-sm font-semibold text-slate-900">{{ $title }}</div>
		</div>
	@endif

	<div class="p-5 bg-slate-50/60 rounded-b-2xl">
		<div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
			{{ $slot }}
		</div>
	</div>
</div>
@props([
	'variant' => 'success', // success|warning|danger|info
	'title' => null,
	'message' => null,
	'autohide' => true,
	'autohideMs' => 3200,
	'dismissible' => true,
])

@php
	$classes = match ($variant) {
		'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
		'danger' => 'border-rose-200 bg-rose-50 text-rose-900',
		'info' => 'border-sky-200 bg-sky-50 text-sky-900',
		default => 'border-emerald-200 bg-emerald-50 text-emerald-900',
	};
@endphp

<div
	@if($autohide)
		x-data="{ show: true }"
		x-init="setTimeout(() => show = false, {{ (int) $autohideMs }})"
		x-show="show"
	@endif
	{{ $attributes->merge(['class' => 'rounded-2xl border p-4 text-sm '.$classes]) }}
>
	<div class="flex items-start justify-between gap-3">
		<div class="min-w-0">
			@if($title)
				<div class="font-semibold">{{ $title }}</div>
			@endif

			@if($message)
				<div class="{{ $title ? 'mt-1' : '' }}">{{ $message }}</div>
			@else
				{{ $slot }}
			@endif
		</div>

		@if($dismissible)
			<button type="button"
				class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-white/40"
				@if($autohide)
					@click="show = false"
				@else
					onclick="this.closest('div').remove()"
				@endif
			>
				✕
			</button>
		@endif
	</div>
</div>
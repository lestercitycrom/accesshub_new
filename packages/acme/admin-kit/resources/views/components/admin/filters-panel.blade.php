@props([
	'title' => 'Filters',
	'open' => false,
	'activeCount' => 0,
])

<details class="group rounded-2xl border border-slate-200 bg-white shadow-sm" @if($open) open @endif>
	<summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4">
		<div class="flex items-center gap-2">
			<div class="text-sm font-semibold text-slate-900">{{ $title }}</div>

			@if((int) $activeCount > 0)
				<x-admin.badge variant="violet">{{ (int) $activeCount }} active</x-admin.badge>
			@endif
		</div>

		<div class="text-xs font-semibold text-slate-500 group-open:hidden">Show</div>
		<div class="text-xs font-semibold text-slate-500 hidden group-open:block">Hide</div>
	</summary>

	<div class="px-5 pb-5">
		{{ $slot }}
	</div>
</details>
@props(['item'])

@php
	use Illuminate\Support\Facades\Route as RouteFacade;

	// Resolve href for an item; admin.server.errors has no named route here.
	$navHref = function (array $i): ?string {
		$r = (string) ($i['route'] ?? '');
		if ($r === 'admin.server.errors') {
			return url('/admin/server');
		}
		return ($r !== '' && RouteFacade::has($r)) ? route($r) : null;
	};
	$navActive = function (array $i): bool {
		$r = (string) ($i['route'] ?? '');
		if ($r === 'admin.server.errors') {
			return request()->path() === 'admin/server';
		}
		return $r !== '' && RouteFacade::has($r) && request()->routeIs($r);
	};

	$children = (array) ($item['children'] ?? []);
	$hasChildren = count($children) > 0;
	$label = (string) ($item['label'] ?? '');
	$icon = (string) ($item['icon'] ?? '');
	$href = $navHref($item);

	$groupActive = $navActive($item);
	foreach ($children as $c) {
		if ($navActive($c)) { $groupActive = true; }
	}
@endphp

@if($hasChildren)
	<div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @click.outside="open = false">
		<button type="button" @click="open = true"
			class="shrink-0 rounded-xl px-3 py-2 text-sm font-semibold transition inline-flex items-center gap-2 {{ $groupActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
			@if($icon !== '')<x-admin.icon :name="$icon" class="h-4 w-4" />@endif
			<span>{{ $label }}</span>
			<span class="text-[10px] text-slate-400" x-text="open ? '▲' : '▼'">▼</span>
		</button>

		<div x-show="open" x-cloak x-transition.origin.top.left
			class="absolute left-0 mt-2 w-56 rounded-2xl border border-white/10 bg-slate-900/95 backdrop-blur shadow-lg overflow-hidden z-50">
			<div class="p-2">
				@foreach($children as $c)
					@php $ch = $navHref($c); $ca = $navActive($c); $ci = (string) ($c['icon'] ?? ''); @endphp
					@if($ch !== null)
						<a href="{{ $ch }}"
							class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold {{ $ca ? 'bg-white/10 text-white' : 'text-slate-200 hover:bg-white/10' }}">
							@if($ci !== '')<x-admin.icon :name="$ci" class="h-4 w-4 text-slate-300" />@endif
							<span>{{ $c['label'] ?? '' }}</span>
						</a>
					@endif
				@endforeach
			</div>
		</div>
	</div>
@elseif($href !== null)
	<a href="{{ $href }}"
		class="shrink-0 rounded-xl px-3 py-2 text-sm font-semibold transition inline-flex items-center gap-2 {{ $groupActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
		@if($icon !== '')<x-admin.icon :name="$icon" class="h-4 w-4" />@endif
		<span>{{ $label }}</span>
	</a>
@endif

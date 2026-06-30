@props(['title' => 'Game Delivery'])
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ $title }} · GlobalGames</title>
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	<style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-50 via-indigo-50/40 to-indigo-100/60 text-slate-900 antialiased">

	{{-- Header --}}
	<header class="sticky top-0 z-40 border-b border-white/60 bg-white/70 backdrop-blur">
		<div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
			{{-- Brand --}}
			<a href="{{ route('delivery.take-order') }}" class="flex shrink-0 items-center gap-2.5">
				<span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-sm">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><rect x="2" y="6" width="20" height="12" rx="5"/><line x1="6.5" y1="11" x2="9.5" y2="11"/><line x1="8" y1="9.5" x2="8" y2="12.5"/><circle cx="15.5" cy="13" r=".7" fill="currentColor"/><circle cx="18" cy="11" r=".7" fill="currentColor"/></svg>
				</span>
				<span class="leading-tight">
					<span class="block text-lg font-extrabold tracking-tight">GlobalGames</span>
					<span class="block text-[11px] font-medium text-slate-500">Game Delivery</span>
				</span>
			</a>

			{{-- Nav (placeholders for now) --}}
			<nav class="hidden items-center gap-7 text-sm font-semibold text-slate-700 lg:flex">
				<a href="{{ route('delivery.how-it-works') }}" class="hover:text-indigo-600">How it works</a>
				<a href="{{ route('delivery.how-it-works') }}#guarantee" class="hover:text-indigo-600">Guarantee</a>
				<a href="{{ route('delivery.how-it-works') }}#faq" class="hover:text-indigo-600">FAQ</a>
				<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="hover:text-indigo-600">Contact us</a>
			</nav>

			{{-- Status + hours widget --}}
			<div class="flex shrink-0 items-center gap-3" id="hoursWidget">
				<span class="hidden items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold sm:inline-flex">
					<span id="hoursDot" class="h-2 w-2 rounded-full bg-emerald-500"></span>
					<span id="hoursStatus">Online</span>
				</span>
				<span class="hidden items-center gap-1.5 text-xs font-semibold text-slate-600 md:inline-flex">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-slate-400"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
					<span id="hoursTime">—</span>
				</span>
				<span class="hidden items-center gap-1.5 text-xs text-slate-600 lg:inline-flex">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-slate-400"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
					<span class="leading-tight"><span class="block text-[10px] uppercase tracking-wide text-slate-400">Support Hours</span><b id="hoursRange" class="block font-bold text-slate-700">09:00 – 23:00 EET</b></span>
				</span>
			</div>
		</div>
	</header>

	<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10">
		{{ $slot }}
	</main>

	<footer class="mx-auto max-w-7xl px-4 pb-10 pt-4 sm:px-6">
		<div class="flex flex-col items-center justify-center gap-1 text-xs text-slate-400 sm:flex-row sm:gap-4">
			<span>© {{ date('Y') }} GlobalGames. All rights reserved.</span>
			<span class="hidden sm:inline">·</span>
			<a href="{{ route('delivery.how-it-works') }}" class="hover:text-slate-600">How it works</a>
			<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="hover:text-slate-600">Terms &amp; Support</a>
		</div>
	</footer>

	<script>
		window.__deliveryHours = @json(config('delivery.working_hours', []));
		(() => {
			const cfg = window.__deliveryHours || {};
			const tz = cfg.timezone || 'Europe/Kyiv';
			const start = Number.isFinite(cfg.start) ? cfg.start : 9;
			const end = Number.isFinite(cfg.end) ? cfg.end : 23;
			const label = cfg.label || 'EET';
			const pad = (n) => String(n).padStart(2, '0');
			const nowTz = () => { try { return new Date(new Date().toLocaleString('en-US', { timeZone: tz })); } catch (e) { return new Date(); } };
			const isOpen = () => { const h = nowTz().getHours(); return end >= 24 ? h >= start : (h >= start && h < end); };
			window.deliveryHoursIsOpen = isOpen;
			window.deliveryHoursEnforced = () => !!cfg.enforce;

			const timeEl = document.getElementById('hoursTime');
			const statusEl = document.getElementById('hoursStatus');
			const dotEl = document.getElementById('hoursDot');
			const rangeEl = document.getElementById('hoursRange');
			if (rangeEl) rangeEl.textContent = `${pad(start)}:00 – ${end >= 24 ? '00' : pad(end)}:00 ${label}`;

			function tick() {
				const t = nowTz();
				if (timeEl) timeEl.textContent = `${pad(t.getHours())}:${pad(t.getMinutes())}:${pad(t.getSeconds())} ${label}`;
				const open = isOpen();
				if (statusEl) statusEl.textContent = open ? 'Online' : 'Offline';
				if (dotEl) dotEl.className = `h-2 w-2 rounded-full ${open ? 'bg-emerald-500' : 'bg-rose-500'}`;
				document.dispatchEvent(new CustomEvent('delivery-hours-tick', { detail: { open } }));
			}
			tick();
			setInterval(tick, 1000);
		})();
	</script>
</body>
</html>

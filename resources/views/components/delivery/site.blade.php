@props(['title' => 'Game Delivery'])
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ $title }} · GlobalGames</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	<style>
		[x-cloak]{display:none!important}
		body{font-family:'Inter',ui-sans-serif,system-ui,sans-serif;background:radial-gradient(circle at 34% 24%,#eaf6ff 0,#f8fbff 34%,#f6faff 100%);}
	</style>
</head>
<body class="min-h-screen text-[#071632] antialiased">

	{{-- Header --}}
	<header class="sticky top-0 z-40 border-b border-[#dde9f8] bg-white/70 backdrop-blur-xl">
		<div class="mx-auto flex h-[76px] max-w-[1500px] items-center justify-between gap-6 px-5 sm:h-[88px] sm:px-9">
			<a href="{{ route('delivery.take-order') }}" class="flex shrink-0 items-center gap-3">
				<img src="{{ asset('site/icons/logo.svg') }}" alt="" class="h-11 w-11">
				<span class="leading-none">
					<span class="block text-[22px] font-extrabold tracking-tight sm:text-[26px]">Global<span class="text-[#0b6bff]">Games</span></span>
					<span class="mt-1 block text-[13px] font-medium text-[#27446f]">Game Delivery</span>
				</span>
			</a>

			<nav class="hidden flex-1 items-center justify-center gap-10 text-[15px] font-bold text-[#0c2750] lg:flex">
				<a href="{{ route('delivery.how-it-works') }}" class="hover:text-[#0b6bff]">How it works</a>
				<a href="{{ route('delivery.how-it-works') }}#guarantee" class="hover:text-[#0b6bff]">Guarantee</a>
				<a href="{{ route('delivery.how-it-works') }}#faq" class="hover:text-[#0b6bff]">FAQ</a>
				<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="hover:text-[#0b6bff]">Contact us</a>
			</nav>

			<div class="flex shrink-0 items-center gap-5 text-[#09224b]" id="hoursWidget">
				<span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-[13px] font-bold shadow-[0_10px_30px_rgba(8,31,74,.08)]">
					<span id="hoursDot" class="h-2 w-2 rounded-full bg-[#16c36a]"></span>
					<span id="hoursStatus">Online</span>
				</span>
				<span class="hidden items-center gap-2 text-[13px] font-semibold md:inline-flex">
					<svg viewBox="0 0 24 24" fill="none" stroke="#7d93b6" stroke-width="2" class="h-5 w-5"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
					<span id="hoursTime">—</span>
				</span>
				<span class="hidden items-center gap-2 lg:inline-flex">
					<img src="{{ asset('site/icons/headset.svg') }}" alt="" class="h-6 w-6">
					<span class="leading-tight"><span class="block text-[11px] font-semibold text-[#63769a]">Support Hours</span><b id="hoursRange" class="block text-[13px] font-bold text-[#09224b]">09:00 – 23:00 EET</b></span>
				</span>
			</div>
		</div>
	</header>

	<main class="mx-auto max-w-[1500px] px-5 py-7 sm:px-9 sm:py-8">
		{{ $slot }}
	</main>

	<footer class="mx-auto max-w-[1500px] px-5 pb-10 pt-3 sm:px-9">
		<div class="flex flex-col items-center justify-between gap-4 border-t border-[#dde9f8] pt-6 text-[13px] text-[#63769a] sm:flex-row">
			<div class="flex flex-wrap items-center justify-center gap-4">
				<span>© {{ date('Y') }} GlobalGames. All rights reserved.</span>
				<a href="{{ route('delivery.how-it-works') }}" class="hover:text-[#0b6bff]">How it works</a>
				<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="hover:text-[#0b6bff]">Terms &amp; Support</a>
			</div>
			<div class="flex items-center gap-3 text-[#0b6bff]">
				@foreach(['M21.5 4.5 2.5 12l5.5 1.8L18 7l-7.5 8 .3 4 2.7-3 4.5 3.2z','M8 12a4 4 0 1 0 8 0 4 4 0 0 0-8 0M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0','M22 5.9c-.7.3-1.5.5-2.3.6.8-.5 1.5-1.3 1.8-2.3-.8.5-1.7.8-2.6 1a4 4 0 0 0-6.8 3.6A11.3 11.3 0 0 1 3.8 4.6a4 4 0 0 0 1.2 5.3c-.6 0-1.2-.2-1.7-.5a4 4 0 0 0 3.2 4 4 4 0 0 1-1.8.1 4 4 0 0 0 3.7 2.8A8 8 0 0 1 2 18a11.3 11.3 0 0 0 6.1 1.8c7.4 0 11.4-6.1 11.4-11.4v-.5c.8-.6 1.5-1.3 2-2z','M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5m5 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10m4.5-1.5a1 1 0 1 0 0 2 1 1 0 0 0 0-2'] as $d)
					<a href="#" class="flex h-9 w-9 items-center justify-center rounded-full bg-[#eaf3ff] hover:bg-[#dbeafe]"><svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="{{ $d }}"/></svg></a>
				@endforeach
			</div>
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
				if (dotEl) dotEl.className = `h-2 w-2 rounded-full ${open ? 'bg-[#16c36a]' : 'bg-[#ef4444]'}`;
				document.dispatchEvent(new CustomEvent('delivery-hours-tick', { detail: { open } }));
			}
			tick();
			setInterval(tick, 1000);
		})();
	</script>
</body>
</html>

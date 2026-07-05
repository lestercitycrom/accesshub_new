@props(['title' => 'Game Delivery'])
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ $title }} · GlobalGames</title>
	<link rel="icon" type="image/x-icon" href="{{ asset('favicon-gg.ico') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-gg-32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-gg-16.png') }}">
	<link rel="apple-touch-icon" href="{{ asset('apple-touch-gg.png') }}">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	<style>
		[x-cloak]{display:none!important}
		body{font-family:'Inter',ui-sans-serif,system-ui,sans-serif;background:radial-gradient(900px 420px at 30% -40px,#efe9fc 0,rgba(239,233,252,0) 60%),radial-gradient(700px 420px at 85% 10%,#ede8fb 0,rgba(237,232,251,0) 55%),#f3f1fb;}
	</style>
</head>
<body class="min-h-screen text-[#17142b] antialiased">

	{{-- Header (like the mockup) --}}
	<header class="sticky top-0 z-40 border-b border-[#e9e5f7]/70 bg-[#f6f4fc]/80 backdrop-blur-xl">
		<div class="mx-auto flex h-[62px] max-w-[1280px] items-center justify-between gap-5 px-5 sm:px-8">
			<a href="{{ route('delivery.take-order') }}" class="flex shrink-0 items-center gap-2.5">
				<img src="{{ asset('site/mock/logo.webp') }}" alt="" class="h-10 w-10 rounded-[12px] select-none" draggable="false">
				<span class="leading-none">
					<span class="block text-[19px] font-extrabold tracking-tight text-[#17142b]">GlobalGames</span>
					<span class="mt-0.5 block text-[11.5px] font-medium text-[#7d7a96]">Game Delivery</span>
				</span>
			</a>

			<nav class="hidden items-center gap-8 text-[14px] font-bold text-[#211d3a] lg:flex">
				<a href="{{ route('delivery.how-it-works') }}" class="hover:text-[#5535fc]">How it works</a>
				<a href="{{ route('delivery.how-it-works') }}#guarantee" class="hover:text-[#5535fc]">Guarantee</a>
				<a href="{{ route('delivery.how-it-works') }}#faq" class="hover:text-[#5535fc]">FAQ</a>
				<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="hover:text-[#5535fc]">Contact us</a>
			</nav>

			<div class="flex shrink-0 items-center gap-4 sm:gap-5" id="hoursWidget">
				<span class="inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-1.5 text-[13px] font-bold text-[#17142b] shadow-[0_6px_18px_rgba(38,24,98,.08)]">
					<span id="hoursDot" class="h-2 w-2 rounded-full bg-[#22c55e]"></span>
					<span id="hoursStatus">Online</span>
				</span>
				<span class="hidden items-center gap-1.5 text-[14px] font-bold tabular-nums text-[#6d63b0] md:inline-flex">
					<img src="{{ asset('site/mock/ic-clock.webp') }}" alt="" class="h-[20px] w-[20px] select-none">
					<span id="hoursTime" class="inline-block min-w-[104px]">—</span>
				</span>
				<span class="hidden items-center gap-2 lg:inline-flex">
					<img src="{{ asset('site/mock/ic-headset.webp') }}" alt="" class="h-[24px] w-[24px] select-none">
					<span class="leading-tight"><span class="block text-[11px] font-semibold text-[#7d7a96]">Support Hours</span><b id="hoursRange" class="block text-[13px] font-extrabold text-[#17142b]">09:00 – 23:00 EET</b></span>
				</span>
			</div>
		</div>
	</header>

	<main class="mx-auto max-w-[1280px] px-5 py-3 sm:px-8 sm:py-3">
		{{ $slot }}
	</main>

	<footer class="mx-auto max-w-[1280px] px-5 pb-3.5 pt-1 sm:px-8">
		<div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-2 text-[11.5px] text-[#8a86a8]">
			<span>© {{ date('Y') }} GlobalGames. All rights reserved.</span>
			<a href="{{ route('delivery.how-it-works') }}" class="hover:text-[#5535fc]">Terms of Service</a>
			<a href="{{ route('delivery.how-it-works') }}#faq" class="hover:text-[#5535fc]">Privacy Policy</a>
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
				if (dotEl) dotEl.className = `h-2 w-2 rounded-full ${open ? 'bg-[#22c55e]' : 'bg-[#ef4444]'}`;
				document.dispatchEvent(new CustomEvent('delivery-hours-tick', { detail: { open } }));
			}
			tick();
			setInterval(tick, 1000);
		})();
	</script>
</body>
</html>

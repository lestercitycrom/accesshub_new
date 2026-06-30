<x-delivery.site title="Track your order">
	@php
		$formAction = ($code ?? null)
			? route('delivery.take-order.coded.store', ['code' => $code])
			: route('delivery.take-order.store');

		// Brand-ish platform glyphs (approximations; swap for real logos later).
		$pf = [
			'PlayStation' => ['color' => 'text-[#0070d1]', 'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7"><path d="M9.5 3.5v13.8l3 1V7.2c0-.6.3-.9.7-.8.6.2.7.8.7 1.4 0 1.9 0 4 2.4 4.9 1.1.4 2.1.5 3 .2V11c-.6.3-1.6.5-2.2 0-.5-.4-.5-1.1-.5-1.8V6.3c0-1.6-.4-2.8-3.1-3.7C12.5 2.1 11 1.8 9.5 3.5Z"/><path d="M14 19.6l5.2-1.9c.6-.2.7-.5.2-.7-.5-.2-1.4-.3-2-.1L14 18.1v1.5Z" opacity=".6"/><path d="M4.6 18.9c-.7-.2-.8-.6-.1-.9l4.9-1.7v1.5l-3.5 1.2c-.4.1-.9.1-1.3-.1Z" opacity=".6"/></svg>'],
			'Xbox' => ['color' => 'text-[#107c10]', 'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="h-7 w-7"><circle cx="12" cy="12" r="9.5"/><path d="M6 5c3 2 5.5 5 6 7 .5-2 3-5 6-7M5 19c1.5-4 4.5-7 7-9 2.5 2 5.5 5 7 9"/></svg>'],
			'Nintendo' => ['color' => 'text-[#e60012]', 'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7"><rect x="4" y="3" width="7" height="18" rx="3.5"/><circle cx="7.5" cy="8" r="1.4" fill="#fff"/><rect x="13" y="3" width="7" height="18" rx="3.5" opacity=".35"/></svg>'],
			'Steam' => ['color' => 'text-slate-800', 'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-7 w-7"><circle cx="12" cy="12" r="9.5"/><circle cx="15.5" cy="9" r="2.6"/><circle cx="9" cy="15" r="2.2" fill="currentColor"/><path d="M2.6 13.5 7 15.3"/></svg>'],
			'Epic Games' => ['color' => 'text-slate-800', 'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7"><rect x="5" y="2.5" width="14" height="19" rx="3"/><path d="M9 7h6v1.6h-4v2h3.4v1.6H11v2.2h4V16H9z" fill="#fff"/></svg>'],
		];
	@endphp

	{{-- Hero + Track card --}}
	<div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-2 lg:gap-10">

		{{-- Hero --}}
		<div class="order-2 lg:order-1">
			<span class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white/70 px-3 py-1 text-xs font-semibold text-indigo-700 shadow-sm">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5z"/></svg>
				Fast. Secure. Reliable.
			</span>

			<h1 class="mt-5 text-5xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl">
				Your game,<br>
				<span class="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">delivered</span> fast
			</h1>
			<p class="mt-5 max-w-md text-base leading-relaxed text-slate-600">
				We deliver your favorite games to your account quickly and safely. Play more, wait less.
			</p>

			{{-- Hero illustration (extracted from the original mockup) --}}
			<div class="relative mt-8 max-w-xl">
				<img src="{{ asset('site/hero.webp') }}" alt="Game delivery" class="w-full select-none rounded-xl" loading="eager" draggable="false">
				<img src="{{ asset('site/platforms.webp') }}" alt="" class="pointer-events-none absolute -left-4 top-1/2 hidden w-14 -translate-y-1/2 select-none drop-shadow-lg sm:block" loading="eager" draggable="false">
			</div>
		</div>

		{{-- Track your order card --}}
		<div class="order-1 lg:order-2">
			<div class="rounded-3xl border border-white bg-white p-6 shadow-xl shadow-indigo-100/50 sm:p-8">
				<h2 class="text-2xl font-bold tracking-tight">Track your order</h2>
				<p class="mt-1 text-sm text-slate-500">Enter your order details to see the status</p>

				@if(session('error'))
					<div class="mt-4 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
						<span>{{ session('error') }}</span>
					</div>
				@endif

				<div id="offlineNotice" class="mt-4 hidden items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
					<span id="offlineNoticeText"></span>
				</div>

				<form method="post" action="{{ $formAction }}" id="takeOrderForm" novalidate class="mt-6 space-y-5">
					@csrf

					{{-- 1. Platform --}}
					<div>
						<label class="text-sm font-semibold text-slate-700">1. Select platform</label>
						<div class="mt-2 grid grid-cols-5 gap-2" role="radiogroup" aria-label="Platform">
							@foreach($platforms as $platform)
								<label class="cursor-pointer">
									<input type="radio" name="platform" value="{{ $platform }}" class="peer sr-only" @checked(old('platform') === $platform) required>
									<div class="flex flex-col items-center gap-1.5 rounded-2xl border border-slate-200 bg-white px-1 py-3 text-center transition peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:ring-2 peer-checked:ring-indigo-200 hover:border-slate-300">
										<span class="{{ $pf[$platform]['color'] ?? 'text-slate-700' }}">{!! $pf[$platform]['svg'] ?? '' !!}</span>
										<span class="text-[10px] font-semibold leading-tight text-slate-600">{{ $platform }}</span>
									</div>
								</label>
							@endforeach
						</div>
						@error('platform') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
					</div>

					{{-- 2. Order number --}}
					<div>
						<label for="order_number" class="text-sm font-semibold text-slate-700">2. Order number</label>
						<div class="relative mt-2">
							<span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-indigo-400">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M7 14h6"/></svg>
							</span>
							<input id="order_number" name="order_number" value="{{ old('order_number') }}" autocomplete="off" required
								placeholder="Enter your order number"
								class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-3 text-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
						</div>
						@error('order_number') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
						@else <p class="mt-1.5 text-xs text-slate-400">You can find it in your order confirmation email</p> @enderror
					</div>

					{{-- 3. Email --}}
					<div>
						<label for="email" class="text-sm font-semibold text-slate-700">3. Email address</label>
						<div class="relative mt-2">
							<span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-indigo-400">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
							</span>
							<input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required
								placeholder="Enter your email address"
								class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-3 text-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
						</div>
						@error('email') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
						@else <p class="mt-1.5 text-xs text-slate-400">The email you used when placing the order</p> @enderror
					</div>

					<button type="submit" id="submitBtn"
						class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:opacity-95 active:translate-y-px disabled:opacity-60">
						<span class="btn-label">Track order</span>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
					</button>

					<div class="flex items-center gap-3 text-xs text-slate-400">
						<span class="h-px flex-1 bg-slate-200"></span>or<span class="h-px flex-1 bg-slate-200"></span>
					</div>

					<a href="#" class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M3 9h18M3 9l2-5h14l2 5M3 9v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9"/></svg>
						Browse our store
					</a>
				</form>
			</div>
		</div>
	</div>

	{{-- Feature row --}}
	<div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
		<div class="flex items-start gap-3 rounded-2xl border border-white bg-white/80 p-5 shadow-sm">
			<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/></svg></div>
			<div><div class="font-bold text-slate-900">Secure Delivery</div><div class="text-sm text-slate-500">Your account is protected</div></div>
		</div>
		<div class="flex items-start gap-3 rounded-2xl border border-white bg-white/80 p-5 shadow-sm">
			<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg></div>
			<div><div class="font-bold text-slate-900">5 – 30 min</div><div class="text-sm text-slate-500">Estimated delivery time</div></div>
		</div>
		<div class="flex items-start gap-3 rounded-2xl border border-white bg-white/80 p-5 shadow-sm">
			<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></div>
			<div><div class="font-bold text-slate-900">Live Support</div><div class="text-sm text-slate-500">We are here to help</div></div>
		</div>
	</div>

	{{-- Telegram community banner --}}
	<div class="mt-6 flex flex-col items-center justify-between gap-4 rounded-2xl border border-white bg-white/80 p-5 shadow-sm sm:flex-row">
		<div class="flex items-center gap-3">
			<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><svg viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6"><path d="M21.5 4.5 2.5 12l5.5 1.8L18 7l-7.5 8 .3 4 2.7-3 4.5 3.2z"/></svg></div>
			<div>
				<div class="font-bold text-slate-900">Join our community</div>
				<div class="text-sm text-slate-500">Get exclusive discounts, news and promo codes by joining our Telegram channel.</div>
			</div>
		</div>
		<div class="flex shrink-0 items-center gap-3">
			<a href="#" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow-md"><svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M21.5 4.5 2.5 12l5.5 1.8L18 7l-7.5 8 .3 4 2.7-3 4.5 3.2z"/></svg> Join Telegram</a>
			<span class="rounded-xl bg-indigo-50 px-3 py-2.5 text-sm font-bold text-indigo-700">10 – 30% OFF</span>
		</div>
	</div>

	<script>
		(() => {
			const form = document.getElementById('takeOrderForm');
			const btn = document.getElementById('submitBtn');
			const label = btn.querySelector('.btn-label');
			const notice = document.getElementById('offlineNotice');
			const noticeText = document.getElementById('offlineNoticeText');
			const cfg = window.__deliveryHours || {};
			const pad = (n) => String(n).padStart(2, '0');
			const rangeText = `${pad(cfg.start ?? 9)}:00 to ${(cfg.end ?? 23) >= 24 ? '00' : pad(cfg.end ?? 23)}:00 ${cfg.label || 'EET'}`;
			let closed = false;

			function applyHours() {
				const enforce = typeof window.deliveryHoursEnforced === 'function' ? window.deliveryHoursEnforced() : !!cfg.enforce;
				const open = typeof window.deliveryHoursIsOpen === 'function' ? window.deliveryHoursIsOpen() : true;
				closed = enforce && !open;
				if (notice) { notice.classList.toggle('hidden', !closed); notice.classList.toggle('flex', closed); }
				if (closed && noticeText) noticeText.textContent = `Orders are accepted during support hours (${rangeText}). Please come back later.`;
				if (!btn.dataset.loading) btn.disabled = closed;
			}
			document.addEventListener('delivery-hours-tick', applyHours);
			applyHours();

			form.addEventListener('submit', (e) => {
				if (closed) { e.preventDefault(); applyHours(); return; }
				if (!form.checkValidity()) return;
				btn.dataset.loading = '1';
				btn.disabled = true;
				label.textContent = 'Checking order…';
			});
		})();
	</script>
</x-delivery.site>

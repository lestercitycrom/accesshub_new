<x-delivery.site title="Track your order">
	@php
		$formAction = ($code ?? null)
			? route('delivery.take-order.coded.store', ['code' => $code])
			: route('delivery.take-order.store');

		// Recognizable platform marks (placeholders — swap for licensed brand assets).
		$pf = [
			'PlayStation' => '<svg viewBox="0 0 48 48" class="h-8 w-8"><path fill="#0070D1" d="M17 7v27.6l6.2 2V12c0-1 .4-1.6 1.2-1.4 1 .3 1.2 1.4 1.2 2.4v7.9c3.9 1.9 7 .2 7-4.8 0-5-1.9-7.2-7.4-9C22.6 7.1 19.6 7 17 7Z"/><path fill="#0070D1" opacity=".85" d="M29.5 34l9.1-3.3c1.1-.4 1.3-1 .3-1.4-1-.4-2.7-.5-3.8-.1l-5.6 2V34Z"/><path fill="#0070D1" opacity=".85" d="M9.6 33c-1.4-.4-1.5-1.3-.2-1.8l8.1-2.9v2.7L11.6 33.1c-.7.2-1.4.2-2-.1Z"/></svg>',
			'Xbox' => '<svg viewBox="0 0 48 48" class="h-8 w-8"><circle cx="24" cy="24" r="20" fill="#107C10"/><path d="M14 13 34 35M34 13 14 35" fill="none" stroke="#fff" stroke-width="3.4" stroke-linecap="round"/></svg>',
			'Nintendo' => '<svg viewBox="0 0 48 48" class="h-8 w-8"><rect x="7" y="6" width="15" height="36" rx="7.5" fill="#E60012"/><circle cx="14.5" cy="15" r="2.9" fill="#fff"/><rect x="26" y="6" width="15" height="36" rx="7.5" fill="#3a3a3a"/></svg>',
			'Steam' => '<svg viewBox="0 0 48 48" class="h-8 w-8"><circle cx="24" cy="24" r="20" fill="#171a21"/><circle cx="30" cy="18" r="5.5" fill="none" stroke="#fff" stroke-width="2.6"/><circle cx="30" cy="18" r="1.9" fill="#fff"/><circle cx="16" cy="30" r="5" fill="#fff"/><path d="M7 27l9 4" stroke="#fff" stroke-width="2.2"/></svg>',
			'Epic Games' => '<svg viewBox="0 0 48 48" class="h-8 w-8"><rect x="7" y="4" width="34" height="40" rx="8" fill="#2a2a2a"/><text x="24" y="21" text-anchor="middle" fill="#fff" font-family="Arial, sans-serif" font-size="10" font-weight="700">EPIC</text><text x="24" y="33" text-anchor="middle" fill="#fff" font-family="Arial, sans-serif" font-size="7.5" letter-spacing="0.5">GAMES</text></svg>',
		];
		$ico = fn ($name) => asset('site/icons/' . $name . '.svg');
	@endphp

	{{-- Hero + Track card --}}
	<div class="grid items-center gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,440px)]">

		{{-- Hero: copy + art side by side (compact, like the concept) --}}
		<div class="order-2 grid items-center gap-6 lg:order-1 lg:grid-cols-[minmax(0,440px)_minmax(0,1fr)]">
			<div class="max-w-[440px]">
				<span class="inline-flex items-center gap-2 rounded-xl bg-[#edf6ff] px-3.5 py-2 text-xs font-extrabold text-[#005be0]">
					<img src="{{ $ico('shield') }}" alt="" class="h-4 w-4"> Fast. Secure. Reliable.
				</span>
				<h1 class="mt-6 text-[40px] font-extrabold leading-[1.05] tracking-[-0.04em] sm:text-[54px]">
					Your game,<br><span class="text-[#0b6bff]">delivered</span> fast
				</h1>
				<p class="mt-5 text-[18px] leading-[1.65] text-[#243b63]">
					We deliver your favorite games to your account quickly and safely.
					<b class="text-[#0b6bff]">Play more</b>, wait less.
				</p>
			</div>
			<img src="{{ asset('site/hero-blue.webp') }}" alt="Game delivery"
				class="mx-auto w-full max-w-[420px] select-none drop-shadow-[0_24px_44px_rgba(11,107,255,.16)]"
				loading="eager" draggable="false">
		</div>

		{{-- Track your order card --}}
		<div class="order-1 lg:order-2">
			<div class="rounded-[22px] border border-[#dde9f8] bg-white/90 p-6 shadow-[0_18px_60px_rgba(8,31,74,.08)] sm:p-7">
				<div class="flex items-start justify-between gap-4">
					<div>
						<h2 class="text-[28px] font-extrabold leading-tight">Track your order</h2>
						<p class="mt-1 text-sm text-[#28456e]">Enter your order details to see the status</p>
					</div>
					<span class="inline-flex shrink-0 items-center gap-1.5 rounded-[10px] bg-[#edfdf4] px-3 py-2 text-[13px] font-extrabold text-[#07974a]">
						<img src="{{ $ico('shield') }}" alt="" class="h-4 w-4"> 100% Secure
					</span>
				</div>

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

				<form method="post" action="{{ $formAction }}" id="takeOrderForm" novalidate class="mt-5 space-y-4">
					@csrf

					{{-- 1. Platform --}}
					<div>
						<label class="text-sm font-extrabold text-[#0c2750]">1. Select platform</label>
						<div class="mt-2.5 grid grid-cols-5 gap-3" role="radiogroup" aria-label="Platform">
							@foreach($platforms as $platform)
								<label class="cursor-pointer">
									<input type="radio" name="platform" value="{{ $platform }}" class="peer sr-only" @checked(old('platform') === $platform) required>
									<div class="relative flex h-[88px] flex-col items-center justify-center gap-1.5 rounded-xl border border-[#dde9f8] bg-white px-1 text-center font-bold text-[#0c2750] transition peer-checked:border-[#0b6bff] peer-checked:text-[#0b6bff] peer-checked:shadow-[0_8px_24px_rgba(11,107,255,.12)] hover:border-[#b9d4f6]">
										<span class="hidden text-[#0b6bff] peer-checked:block absolute right-1.5 top-1.5"><svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
										<span class="flex h-8 w-8 items-center justify-center">{!! $pf[$platform] ?? '' !!}</span>
										<span class="text-[11px] font-bold leading-tight">{{ $platform }}</span>
									</div>
								</label>
							@endforeach
						</div>
						@error('platform') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
					</div>

					{{-- 2. Order number --}}
					<div>
						<label for="order_number" class="text-sm font-extrabold text-[#0c2750]">2. Order number</label>
						<div class="relative mt-2">
							<span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2"><img src="{{ $ico('order') }}" alt="" class="h-5 w-5"></span>
							<input id="order_number" name="order_number" value="{{ old('order_number') }}" autocomplete="off" required
								placeholder="Enter your order number"
								class="h-[54px] w-full rounded-xl border border-[#dde9f8] bg-white pl-12 pr-4 text-sm text-[#071632] outline-none transition focus:border-[#0b6bff] focus:ring-2 focus:ring-[#0b6bff]/15">
						</div>
						@error('order_number') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
						@else <p class="mt-1.5 text-xs text-[#63769a]">You can find it in your order confirmation email</p> @enderror
					</div>

					{{-- 3. Email --}}
					<div>
						<label for="email" class="text-sm font-extrabold text-[#0c2750]">3. Email address</label>
						<div class="relative mt-2">
							<span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2"><img src="{{ $ico('mail') }}" alt="" class="h-5 w-5"></span>
							<input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required
								placeholder="Enter your email address"
								class="h-[54px] w-full rounded-xl border border-[#dde9f8] bg-white pl-12 pr-4 text-sm text-[#071632] outline-none transition focus:border-[#0b6bff] focus:ring-2 focus:ring-[#0b6bff]/15">
						</div>
						@error('email') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
						@else <p class="mt-1.5 text-xs text-[#63769a]">The email you used when placing the order</p> @enderror
					</div>

					<button type="submit" id="submitBtn"
						class="flex h-[56px] w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-[#0b8dff] to-[#005be0] text-[16px] font-extrabold text-white shadow-[0_14px_28px_rgba(11,107,255,.22)] transition hover:opacity-95 active:translate-y-px disabled:opacity-60">
						<span class="btn-label">Track order</span>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="h-4 w-4"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
					</button>

					<div class="flex items-center gap-3 text-[13px] font-bold text-[#7b8aaa]"><span class="h-px flex-1 bg-[#dde9f8]"></span>or<span class="h-px flex-1 bg-[#dde9f8]"></span></div>

					<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="flex h-[54px] w-full items-center justify-center gap-2 rounded-xl border border-[#0b6bff] bg-white text-[15px] font-extrabold text-[#0b6bff] transition hover:bg-[#f3f9ff]">
						<img src="{{ $ico('order') }}" alt="" class="h-5 w-5"> Browse our store
					</a>
				</form>
			</div>
		</div>
	</div>

	{{-- Stats strip --}}
	<div class="mt-7 grid grid-cols-1 gap-0 overflow-hidden rounded-[18px] border border-[#dde9f8] bg-white/90 shadow-[0_18px_60px_rgba(8,31,74,.08)] sm:grid-cols-2 lg:grid-cols-4">
		@php
			$stats = [
				['shield', 'Secure Delivery', '100% protected'],
				['lightning', '5 – 30 min', 'Estimated delivery'],
				['headset', 'Live Support', 'We are here to help'],
				['star', '50,000+', 'Orders delivered'],
			];
		@endphp
		@foreach($stats as $i => [$icon, $b, $small])
			<div class="flex items-center gap-4 px-6 py-5 {{ $i < 3 ? 'lg:border-r' : '' }} {{ $i < 3 ? 'border-b sm:border-b-0' : '' }} border-[#dde9f8]">
				<span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#eff7ff]"><img src="{{ $ico($icon) }}" alt="" class="h-6 w-6"></span>
				<span class="leading-tight"><b class="block text-[16px] text-[#071632]">{{ $b }}</b><small class="text-[13px] text-[#29466f]">{{ $small }}</small></span>
			</div>
		@endforeach
	</div>

	{{-- Bottom grid --}}
	<div class="mt-7 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-[1.35fr_.8fr_1.8fr_1fr]">
		{{-- Join community --}}
		<div class="relative overflow-hidden rounded-[18px] border border-[#dde9f8] bg-white/90 p-6 shadow-[0_18px_60px_rgba(8,31,74,.08)]">
			<img src="{{ asset('site/illus-community.webp') }}" alt="" class="pointer-events-none absolute -right-3 top-1/2 hidden w-[170px] -translate-y-1/2 select-none sm:block" draggable="false">
			<div class="relative z-10 max-w-[64%]">
				<h3 class="text-[20px] font-extrabold">Join our community</h3>
				<p class="mt-3 text-sm leading-[1.55] text-[#29466f]">Get exclusive discounts, news and promo codes by joining our Telegram channel.</p>
				<a href="#" class="mt-4 inline-flex items-center gap-2 rounded-[10px] bg-[#0b6bff] px-5 py-3 text-sm font-extrabold text-white"><svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M21.5 4.5 2.5 12l5.5 1.8L18 7l-7.5 8 .3 4 2.7-3 4.5 3.2z"/></svg> Join Telegram</a>
			</div>
		</div>
		{{-- Offer --}}
		<div class="flex flex-col items-center justify-center rounded-[18px] border border-[#dde9f8] bg-white/90 p-6 text-center shadow-[0_18px_60px_rgba(8,31,74,.08)]">
			<div class="text-[26px] font-extrabold text-[#0b6bff]">10 – 30% OFF</div>
			<div class="mt-1 text-sm text-[#29466f]">For our subscribers</div>
			<img src="{{ asset('site/illus-offer.webp') }}" alt="" class="mt-3 w-24 select-none" draggable="false">
		</div>
		{{-- How it works mini --}}
		<div class="rounded-[18px] border border-[#dde9f8] bg-white/90 p-6 shadow-[0_18px_60px_rgba(8,31,74,.08)]">
			<h3 class="text-[20px] font-extrabold">How it works</h3>
			<div class="mt-4 grid grid-cols-3 gap-4">
				@foreach([['1','Place order','Enter your details and complete the order.'],['2','We deliver','We deliver your game account quickly.'],['3','Secure & enjoy','Access your game and enjoy the best experience.']] as [$n,$t,$d])
					<div>
						<span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#eaf3ff] text-[13px] font-extrabold text-[#0b6bff]">{{ $n }}</span>
						<b class="mt-2 block text-[14px] text-[#071632]">{{ $t }}</b>
						<p class="mt-1 text-[12px] leading-[1.45] text-[#29466f]">{{ $d }}</p>
					</div>
				@endforeach
			</div>
		</div>
		{{-- Trusted --}}
		<div class="rounded-[18px] border border-[#dde9f8] bg-white/90 p-6 shadow-[0_18px_60px_rgba(8,31,74,.08)]">
			<h3 class="text-[18px] font-extrabold leading-tight">Trusted by gamers worldwide</h3>
			<p class="mt-2 text-[13px] leading-[1.5] text-[#29466f]">We partner with the most trusted platforms and payment providers.</p>
			<div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-[#1b3a73]">
				<span class="text-[16px] font-black italic text-[#003087]">Pay<span class="text-[#009cde]">Pal</span></span>
				<span class="inline-flex items-center gap-1 text-[14px] font-extrabold text-[#00b67a]"><svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.3l1.6-6.8L2.2 8.9l6.9-.6z"/></svg>Trustpilot</span>
				<span class="text-[16px] font-black italic text-[#1a1f71]">VISA</span>
				<span class="text-[13px] text-[#63769a]">and more</span>
			</div>
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

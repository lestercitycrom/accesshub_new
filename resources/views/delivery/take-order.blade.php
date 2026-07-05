<x-delivery.site title="Track your order">
	@php
		$formAction = ($code ?? null)
			? route('delivery.take-order.coded.store', ['code' => $code])
			: route('delivery.take-order.store');
		$mock = fn ($name) => asset('site/mock/' . $name . '.webp');
	@endphp

	{{-- Hero + Track card (layout of the original mockup) --}}
	<div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,420px)]">

		{{-- Hero: full composition cut from the mockup, live copy overlaid top-left.
		     Height is capped to the viewport so the whole page fits one screen;
		     on short screens the image bottom is cropped, never the layout. --}}
		<div class="relative order-2 lg:order-1">
			<div class="relative z-10 max-w-[260px] lg:absolute lg:left-[1.5%] lg:top-0">
				<span class="inline-flex items-center gap-2 rounded-full bg-[#f0e9fd] px-3 py-1.5 text-[11px] font-extrabold text-[#5b3df5]">
					<img src="{{ $mock('ic-shield') }}" alt="" class="h-4 w-4"> Fast. Secure. Reliable.
				</span>
				<h1 class="mt-3 text-[30px] font-extrabold leading-[1.04] tracking-[-0.03em] text-[#17142b] sm:text-[33px]">
					Your game,<br><span class="text-[#5535fc]">delivered</span> fast
				</h1>
				<p class="mt-2.5 text-[12.5px] leading-[1.55] text-[#4c4964]">
					We deliver your favorite games to your account quickly and safely.<br>
					Play more, wait less.
				</p>
			</div>
			<img src="{{ $mock('hero') }}" alt="Game delivery"
				class="w-full select-none object-cover object-top lg:max-h-[calc(100vh-240px)]"
				style="mask-image:linear-gradient(to right,black 96%,transparent 100%);-webkit-mask-image:linear-gradient(to right,black 96%,transparent 100%);"
				loading="eager" draggable="false">
		</div>

		{{-- Right column: Track card + three feature cards --}}
		<div class="order-1 lg:order-2">
			<div class="rounded-2xl border border-[#e9e5f7] bg-white p-4 shadow-[0_16px_44px_rgba(38,24,98,.07)]">
				<h2 class="text-[20px] font-extrabold leading-tight text-[#17142b]">Track your order</h2>
				<p class="mt-0.5 text-[12.5px] text-[#8a86a8]">Enter your order details to see the status</p>

				@if(session('error'))
					<div class="mt-4 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
						<span>{{ session('error') }}</span>
					</div>
				@endif

				<div id="offlineNotice" class="mt-2 hidden items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[11.5px] leading-snug text-amber-800">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-3.5 w-3.5 shrink-0"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
					<span id="offlineNoticeText"></span>
				</div>

				<form method="post" action="{{ $formAction }}" id="takeOrderForm" novalidate class="mt-2 space-y-2">
					@csrf

					{{-- 1. Platform --}}
					<div>
						<label class="text-[13px] font-extrabold text-[#211d3a]">1. Select platform</label>
						<div class="mt-2 grid grid-cols-5 gap-1.5" role="radiogroup" aria-label="Platform">
							@foreach($platforms as $platform)
								<label class="cursor-pointer">
									<input type="radio" name="platform" value="{{ $platform }}" class="peer sr-only" @checked(old('platform') === $platform) required>
									<div class="flex h-[52px] flex-col items-center justify-center gap-1 rounded-[10px] border border-[#e9e5f7] bg-white px-0.5 text-center text-[#211d3a] transition peer-checked:border-[#5b3df5] peer-checked:text-[#5535fc] hover:border-[#c9befa]">
										<img src="{{ asset('site/brands/' . ['PlayStation'=>'playstation','Xbox'=>'xbox','Nintendo'=>'nintendoswitch','Steam'=>'steam','Epic Games'=>'epicgames'][$platform] . '.svg') }}" alt="" class="h-[22px] w-[22px] select-none" draggable="false">
										<span class="text-[9.5px] font-bold leading-none">{{ $platform }}</span>
									</div>
								</label>
							@endforeach
						</div>
						@error('platform') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
					</div>

					{{-- 2. Order number --}}
					<div>
						<label for="order_number" class="text-[13px] font-extrabold text-[#211d3a]">2. Order number</label>
						<div class="relative mt-1.5">
							<img src="{{ $mock('ic-order') }}" alt="" class="pointer-events-none absolute left-2.5 top-1/2 h-[22px] w-[22px] -translate-y-1/2 select-none">
							<input id="order_number" name="order_number" value="{{ old('order_number') }}" autocomplete="off" required
								placeholder="Enter your order number"
								class="h-[34px] w-full rounded-[10px] border border-[#e9e5f7] bg-white pl-10 pr-3 text-[13px] text-[#17142b] outline-none transition placeholder:text-[#b2b3c8] focus:border-[#5b3df5] focus:ring-2 focus:ring-[#5b3df5]/12">
						</div>
						@error('order_number') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
						@else <p class="ml-6 mt-0.5 text-[10.5px] text-[#9a94c0]">You can find it in your order confirmation email</p> @enderror
					</div>

					{{-- 3. Email --}}
					<div>
						<label for="email" class="text-[13px] font-extrabold text-[#211d3a]">3. Email address</label>
						<div class="relative mt-1.5">
							<img src="{{ $mock('ic-mail') }}" alt="" class="pointer-events-none absolute left-2.5 top-1/2 h-[22px] w-[22px] -translate-y-1/2 select-none">
							<input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required
								placeholder="Enter your email address"
								class="h-[34px] w-full rounded-[10px] border border-[#e9e5f7] bg-white pl-10 pr-3 text-[13px] text-[#17142b] outline-none transition placeholder:text-[#b2b3c8] focus:border-[#5b3df5] focus:ring-2 focus:ring-[#5b3df5]/12">
						</div>
						@error('email') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
						@else <p class="mt-0.5 text-center text-[10.5px] text-[#9a94c0]">The email you used when placing the order</p> @enderror
					</div>

					<button type="submit" id="submitBtn"
						class="relative flex h-[34px] w-full items-center justify-center rounded-[10px] bg-[#4627ee] text-[14px] font-extrabold text-white shadow-[0_10px_24px_rgba(70,39,238,.28)] transition hover:bg-[#3b1fd6] active:translate-y-px disabled:opacity-60">
						<span class="btn-label">Track order</span>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="absolute right-4 h-4 w-4"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
					</button>

					<div class="text-center text-[12px] font-semibold text-[#9a94c0]">or</div>

					<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener"
						class="flex h-[34px] w-full items-center justify-center gap-2 rounded-[10px] border border-[#e2dcf6] bg-white text-[13.5px] font-extrabold text-[#4627ee] transition hover:bg-[#faf8ff]">
						<img src="{{ $mock('ic-store') }}" alt="" class="h-[18px] w-[18px] select-none"> Browse our store
					</a>
				</form>
			</div>

			{{-- Feature cards (compact horizontal row so the page fits one screen) --}}
			<div class="mt-2.5 grid grid-cols-3 gap-2">
				@php
					$features = [
						['feat-secure', 'Secure Delivery', 'Your account is protected'],
						['feat-time', '5 – 30 min', 'Estimated delivery time'],
						['feat-support', 'Live Support', 'We are here to help'],
					];
				@endphp
				@foreach($features as [$icon, $b, $small])
					<div class="flex items-center gap-2 rounded-xl border border-[#e9e5f7] bg-white/80 px-2.5 py-1.5">
						<img src="{{ $mock($icon) }}" alt="" class="h-9 w-9 shrink-0 select-none" draggable="false">
						<span class="min-w-0 leading-tight"><b class="block text-[12px] text-[#17142b]">{{ $b }}</b><small class="block text-[10px] leading-snug text-[#8a86a8]">{{ $small }}</small></span>
					</div>
				@endforeach
			</div>
		</div>
	</div>

	{{-- Join community bar (full width, tight so the page fits one screen) --}}
	<div class="mt-3 flex flex-col items-center justify-between gap-3 rounded-2xl border border-[#e9e5f7] bg-[#f7f6fc] px-5 py-2.5 sm:flex-row">
		<div class="flex items-center gap-3">
			<img src="{{ $mock('gift') }}" alt="" class="h-10 w-10 shrink-0 select-none" draggable="false">
			<div>
				<h3 class="text-[14.5px] font-extrabold leading-tight text-[#17142b]">Join our community</h3>
				<p class="text-[11.5px] leading-[1.4] text-[#6d6a86]">Get exclusive discounts, news and promo codes by joining our Telegram channel.</p>
			</div>
		</div>
		<div class="flex shrink-0 items-center gap-2.5">
			<a href="https://t.me/GlobalGames7" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-[10px] border border-[#e2dcf6] bg-white px-3.5 py-1.5 text-[12.5px] font-extrabold text-[#211d3a] transition hover:border-[#c9befa]">
				<svg viewBox="0 0 24 24" fill="#5b3df5" class="h-4 w-4"><path d="M21.5 4.5 2.5 12l5.5 1.8L18 7l-7.5 8 .3 4 2.7-3 4.5 3.2z"/></svg> Join Telegram
			</a>
			<span class="rounded-[10px] bg-[#eceafe] px-3.5 py-1.5 text-[12.5px] font-extrabold text-[#3a3654]">10 – 30% OFF</span>
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

<x-delivery.site title="How it works">
	<div class="mx-auto max-w-4xl">

		{{-- Hero --}}
		<div class="text-center">
			<span class="inline-flex items-center gap-2 rounded-full border border-[#ddd2f7] bg-white/70 px-3 py-1 text-xs font-semibold text-[#5b3df5] shadow-sm">Help center</span>
			<h1 class="mt-4 text-4xl font-extrabold tracking-tight sm:text-5xl">How it works</h1>
			<p class="mx-auto mt-3 max-w-xl text-slate-600">Buy on the marketplace, open your delivery link, and our operator delivers your game account — usually within 5–30 minutes.</p>
		</div>

		{{-- Steps --}}
		<div id="how" class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
			@php
				$steps = [
					['1', 'Buy on the marketplace', 'Purchase the game offer and get your unique delivery link.'],
					['2', 'Open your link', 'Open the link, pick your platform and enter your order number and email.'],
					['3', 'Operator delivers', 'Keep the page open — it updates automatically while we prepare your account.'],
					['4', 'Play', 'Get your account details (and a connection code step for consoles), then play.'],
				];
			@endphp
			@foreach($steps as [$n, $t, $d])
				<div class="rounded-2xl border border-white bg-white p-5 shadow-sm">
					<div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#5b3df5] to-[#5b3df5] text-sm font-bold text-white">{{ $n }}</div>
					<div class="mt-3 font-bold text-slate-900">{{ $t }}</div>
					<div class="mt-1 text-sm text-slate-500">{{ $d }}</div>
				</div>
			@endforeach
		</div>

		{{-- Per-platform install instructions --}}
		@if($instructions->count())
			<h2 class="mt-12 text-2xl font-bold tracking-tight">Installation by platform</h2>
			<div class="mt-4 space-y-3">
				@foreach($instructions as $ins)
					<details class="group rounded-2xl border border-white bg-white p-5 shadow-sm">
						<summary class="flex cursor-pointer list-none items-center justify-between gap-3">
							<span class="font-bold text-slate-900">{{ $ins->title ?: $ins->platform }}</span>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 text-slate-400 transition group-open:rotate-180"><polyline points="6 9 12 15 18 9"/></svg>
						</summary>
						<div class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-slate-600">{{ $ins->body }}</div>
					</details>
				@endforeach
			</div>
		@endif

		{{-- FAQ --}}
		<h2 id="faq" class="mt-12 scroll-mt-24 text-2xl font-bold tracking-tight">FAQ</h2>
		<div class="mt-4 space-y-3">
			@php
				$faq = [
					['How long does delivery take?', 'Usually 5–30 minutes during support hours (09:00–23:00 EET). Outside these hours your order is delivered when support is back online.'],
					['I submitted my order — what happens next?', 'An operator verifies your purchase and prepares your account. Keep the order page open: it refreshes automatically and shows your account details when ready.'],
					['Can I change the account login or password?', 'No. Changing the credentials will break access to the game and the order cannot be restored. Keep them as delivered.'],
					['My console asks for a connection code — what do I do?', 'For console platforms, enter the code shown on your console screen on the order page. An operator will then finish the connection.'],
					['My link says it expired.', 'Delivery links are valid for a limited time. If yours expired, request a new link from the seller via the marketplace chat.'],
				];
			@endphp
			@foreach($faq as [$q, $a])
				<details class="group rounded-2xl border border-white bg-white p-5 shadow-sm">
					<summary class="flex cursor-pointer list-none items-center justify-between gap-3">
						<span class="font-semibold text-slate-900">{{ $q }}</span>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 text-slate-400 transition group-open:rotate-180"><polyline points="6 9 12 15 18 9"/></svg>
					</summary>
					<div class="mt-3 text-sm leading-relaxed text-slate-600">{{ $a }}</div>
				</details>
			@endforeach
		</div>

		{{-- Guarantee --}}
		<div id="guarantee" class="mt-12 scroll-mt-24 overflow-hidden rounded-3xl border border-white bg-gradient-to-r from-[#efeafb] to-[#e2d8fa] p-6 shadow-sm sm:p-8">
			<div class="flex items-start gap-4">
				<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-emerald-600 shadow-sm">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/></svg>
				</div>
				<div>
					<h2 class="text-2xl font-bold tracking-tight">Our guarantee</h2>
					<p class="mt-2 max-w-2xl text-slate-600">Every order is delivered through the marketplace with buyer protection. If anything goes wrong with your delivery, contact the seller through the marketplace chat and we will make it right.</p>
					<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#7c5cff] to-[#4627ee] px-4 py-2.5 text-sm font-bold text-white shadow-md">Contact the seller</a>
				</div>
			</div>
		</div>

		<div class="mt-10 text-center">
			<a href="{{ route('delivery.take-order') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#7c5cff] to-[#4627ee] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-200">
				Track your order
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
			</a>
		</div>
	</div>
</x-delivery.site>

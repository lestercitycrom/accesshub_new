<x-delivery.site :title="'Order ' . $payload['order_number']">
	@php $mock = fn ($n) => asset('site/mock/' . $n . '.webp'); @endphp

	<div class="mx-auto max-w-[1240px]"
		id="orderApp"
		data-status-url="{{ route('delivery.order.status', ['token' => $order->token]) }}"
		data-code-url="{{ route('delivery.order.connection-code.store', ['token' => $order->token]) }}"
		data-created="{{ $order->created_at?->format('d M Y, H:i') }}"
		data-art-processing="{{ $mock('banner-processing') }}"
		data-art-delivered="{{ $mock('banner-delivered') }}"
		data-step-check="{{ $mock('step-check') }}"
		data-step-gear="{{ $mock('step-gear-active') }}"
		data-step-truck="{{ $mock('step-truck-pending') }}"
		data-step-box="{{ $mock('step-box-pending') }}">

		{{-- Top bar --}}
		<div class="mb-4 flex items-center justify-between gap-3">
			<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-[13px] font-bold text-[#6d6a86] hover:text-[#5535fc]">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="h-4 w-4"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
				Back to store
			</a>
			<span class="inline-flex items-center gap-2 text-[13.5px] font-extrabold text-[#17142b]">
				Order #<span id="orderNumber">{{ $payload['order_number'] }}</span>
				<button type="button" class="copy-btn rounded p-0.5 hover:bg-[#eceafe]" data-copy="{{ $payload['order_number'] }}" title="Copy order number">
					<img src="{{ $mock('ic-copy') }}" alt="Copy" class="h-[18px] w-[18px] select-none">
				</button>
			</span>
		</div>

		{{-- Status banner --}}
		<div class="relative min-h-[236px] overflow-hidden rounded-[22px] bg-gradient-to-r from-[#efeaff] via-[#e3d9fb] to-[#c8b6f4] shadow-[0_16px_44px_rgba(38,24,98,.10)]">
			<img id="bannerArt" src="{{ $mock('banner-processing') }}" alt=""
				class="pointer-events-none absolute right-0 top-0 hidden h-full w-auto select-none object-cover md:block"
				style="mask-image:linear-gradient(to right,transparent,black 18%);-webkit-mask-image:linear-gradient(to right,transparent,black 18%);" draggable="false">
			<div class="relative z-10 max-w-[420px] px-7 py-8 sm:px-9 sm:py-10">
				<p class="text-[14px] font-semibold text-[#6d63b0]">Your order is</p>
				<h1 id="bannerBig" class="mt-1 text-[38px] font-extrabold tracking-[-0.02em] text-[#17142b] sm:text-[46px]">Processing</h1>
				<p id="bannerSub" class="mt-2.5 text-[13.5px] leading-[1.6] text-[#4c4964]"></p>
				<p id="bannerEta" class="mt-3.5 inline-flex items-center gap-2 text-[13.5px] font-bold text-[#17142b]">
					<img src="{{ $mock('ic-clock') }}" alt="" class="h-[18px] w-[18px] select-none">
					<span id="bannerEtaText">Estimated time: <b class="text-[#5535fc]">5 – 30 minutes</b></span>
				</p>
			</div>
		</div>

		{{-- Stepper --}}
		<div id="stepper" class="mt-8 hidden sm:block"></div>

		{{-- Tabs (multi-game) --}}
		<div class="mt-6 hidden flex-wrap gap-2" id="tabBar"></div>

		{{-- Info cards --}}
		<div class="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
			@php
				$infoIcons = ['info-platform' => 'Platform', 'info-game' => 'Game', 'info-order' => 'Order number', 'info-date' => 'Order placed', 'info-mail' => 'Email'];
			@endphp
			<div class="flex items-center gap-3 rounded-2xl border border-[#e9e5f7] bg-white p-3.5 shadow-[0_8px_24px_rgba(38,24,98,.05)]">
				<img src="{{ $mock('info-platform') }}" alt="" class="h-10 w-10 shrink-0 select-none rounded-[10px]">
				<div class="min-w-0"><div class="text-[10px] font-bold uppercase tracking-[.06em] text-[#8a86a8]">Platform</div>
					<div class="mt-0.5 flex items-center gap-1.5 truncate text-[13.5px] font-extrabold text-[#17142b]" id="dPlatformWrap"><span id="dPlatform">{{ $payload['platform'] }}</span></div></div>
			</div>
			<div class="flex items-center gap-3 rounded-2xl border border-[#e9e5f7] bg-white p-3.5 shadow-[0_8px_24px_rgba(38,24,98,.05)]">
				<img src="{{ $mock('info-game') }}" alt="" class="h-10 w-10 shrink-0 select-none rounded-[10px]">
				<div class="min-w-0"><div class="text-[10px] font-bold uppercase tracking-[.06em] text-[#8a86a8]">Game</div>
					<div class="mt-0.5 truncate text-[13.5px] font-extrabold text-[#17142b]" id="dGame">—</div></div>
			</div>
			<div class="flex items-center gap-3 rounded-2xl border border-[#e9e5f7] bg-white p-3.5 shadow-[0_8px_24px_rgba(38,24,98,.05)]">
				<img src="{{ $mock('info-order') }}" alt="" class="h-10 w-10 shrink-0 select-none rounded-[10px]">
				<div class="min-w-0"><div class="text-[10px] font-bold uppercase tracking-[.06em] text-[#8a86a8]">Order number</div>
					<div class="mt-0.5 truncate text-[13.5px] font-extrabold text-[#17142b]" id="dOrderNumber">{{ $payload['order_number'] }}</div></div>
			</div>
			<div class="flex items-center gap-3 rounded-2xl border border-[#e9e5f7] bg-white p-3.5 shadow-[0_8px_24px_rgba(38,24,98,.05)]">
				<img src="{{ $mock('info-date') }}" alt="" class="h-10 w-10 shrink-0 select-none rounded-[10px]">
				<div class="min-w-0"><div class="text-[10px] font-bold uppercase tracking-[.06em] text-[#8a86a8]">Order placed</div>
					<div class="mt-0.5 text-[13.5px] font-extrabold leading-tight text-[#17142b]">{{ $order->created_at?->format('d M Y') }}<span class="block text-[11px] font-semibold text-[#8a86a8]">at {{ $order->created_at?->format('H:i:s') }}</span></div></div>
			</div>
			<div class="col-span-2 flex items-center gap-3 rounded-2xl border border-[#e9e5f7] bg-white p-3.5 shadow-[0_8px_24px_rgba(38,24,98,.05)] sm:col-span-1">
				<img src="{{ $mock('info-mail') }}" alt="" class="h-10 w-10 shrink-0 select-none rounded-[10px]">
				<div class="min-w-0"><div class="text-[10px] font-bold uppercase tracking-[.06em] text-[#8a86a8]">Email</div>
					<div class="mt-0.5 truncate text-[13.5px] font-extrabold text-[#17142b]" id="dEmail">—</div></div>
			</div>
		</div>

		{{-- Connection code (QR platforms) --}}
		<section class="mt-6 hidden rounded-2xl border border-[#e9e5f7] bg-white p-6 shadow-[0_8px_24px_rgba(38,24,98,.05)]" id="connectionCard">
			<h2 class="text-[16px] font-extrabold text-[#17142b]">Console connection</h2>
			<p class="mt-1 text-[13px] text-[#6d6a86]" id="connDesc">Enter the code shown on your console screen to finish the connection.</p>
			<form id="connForm" class="mt-4 hidden">
				@csrf
				<label for="connection_code" class="text-[13px] font-extrabold text-[#211d3a]">Connection code</label>
				<input id="connection_code" name="connection_code" maxlength="8" minlength="6" autocomplete="off" autocapitalize="characters" spellcheck="false"
					placeholder="6–8 characters"
					class="mt-2 h-[40px] w-full rounded-[10px] border border-[#e9e5f7] bg-white px-3 text-[13px] uppercase tracking-[0.12em] text-[#17142b] outline-none placeholder:normal-case placeholder:tracking-normal placeholder:text-[#b2b3c8] focus:border-[#5b3df5] focus:ring-2 focus:ring-[#5b3df5]/12">
				<p class="mt-2 text-[11.5px] text-[#9a94c0]" id="attemptsText"></p>
				<div class="mt-3 hidden items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700" id="connError">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
					<span id="connErrorText"></span>
				</div>
				<button type="submit" id="connSubmit" class="mt-3 inline-flex h-[40px] items-center justify-center rounded-[10px] bg-[#4627ee] px-6 text-[13.5px] font-extrabold text-white shadow-[0_10px_24px_rgba(70,39,238,.28)] transition hover:bg-[#3b1fd6] disabled:opacity-60">
					<span class="btn-label">Send code</span>
				</button>
			</form>
			<div class="mt-4 hidden items-start gap-2 rounded-xl border border-[#ddd2f7] bg-[#f4f0fd] px-3 py-2.5 text-sm text-[#4c3fa8]" id="connProgress">
				<img src="{{ $mock('ic-clock') }}" alt="" class="mt-0.5 h-4 w-4 shrink-0"><span id="connProgressText"></span>
			</div>
			<div class="mt-4 hidden items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700" id="connLocked">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
				<span id="connLockedText"></span>
			</div>
		</section>

		{{-- Bottom: three columns, swapped by state --}}
		<div class="mt-7 grid grid-cols-1 gap-4 lg:grid-cols-3">

			{{-- A) What's happening? (pre-delivery) --}}
			<div id="happeningCard" class="rounded-2xl border border-[#e9e5f7] bg-white p-6 shadow-[0_8px_24px_rgba(38,24,98,.05)]">
				<h2 class="text-[16px] font-extrabold text-[#17142b]">What's happening?</h2>
				<ol class="mt-4 space-y-4" id="happeningList"></ol>
			</div>

			{{-- B) Your account details (delivered) --}}
			<div id="accountCard" class="hidden rounded-2xl border border-[#e9e5f7] bg-white p-6 shadow-[0_8px_24px_rgba(38,24,98,.05)]">
				<div class="flex items-center gap-2">
					<h2 class="text-[16px] font-extrabold text-[#17142b]">Your account details</h2>
					<img src="{{ $mock('ic-shield') }}" alt="" class="h-4 w-4 select-none">
				</div>
				<p class="mt-1 text-[12.5px] text-[#6d6a86]">Use the credentials below to log in to your account.</p>
				<div class="mt-3.5 space-y-2" id="accountBlock"></div>
				<div class="mt-3.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-[12px] leading-[1.55] text-rose-700">
					<b class="font-extrabold text-rose-600">IMPORTANT</b><br>
					Do not change the account login or password. Doing so may cause a temporary loss of access to the game.<br>
					If you have any questions, please contact me directly through the chat.
				</div>
			</div>

			{{-- C) What to do next (pre-delivery) --}}
			<div id="todoCard" class="rounded-2xl border border-[#e9e5f7] bg-white p-6 shadow-[0_8px_24px_rgba(38,24,98,.05)]">
				<h2 class="text-[16px] font-extrabold text-[#17142b]">What to do next</h2>
				<ul class="mt-4 space-y-3.5 text-[13px] font-semibold text-[#211d3a]">
					<li class="flex items-center gap-3"><img src="{{ $mock('todo-password') }}" alt="" class="h-[26px] w-[26px] shrink-0 select-none rounded-[8px]"> Change the password as soon as possible</li>
					<li class="flex items-center gap-3"><img src="{{ $mock('todo-security') }}" alt="" class="h-[26px] w-[26px] shrink-0 select-none rounded-[8px]"> Do not change email or security settings</li>
					<li class="flex items-center gap-3"><img src="{{ $mock('todo-doc') }}" alt="" class="h-[26px] w-[26px] shrink-0 select-none rounded-[8px]"> Follow the instructions below carefully</li>
					<li class="flex items-center gap-3"><img src="{{ $mock('todo-game') }}" alt="" class="h-[26px] w-[26px] shrink-0 select-none rounded-[8px]"> Enjoy your game!</li>
				</ul>
			</div>

			{{-- Instructions (always) --}}
			<div class="relative overflow-hidden rounded-2xl border border-[#e9e5f7] bg-white p-6 shadow-[0_8px_24px_rgba(38,24,98,.05)]" id="instrCard">
				<h2 class="text-[16px] font-extrabold text-[#17142b]">Instructions</h2>
				<div class="relative z-10 mt-3.5 max-w-[75%] text-[13px] leading-[1.7] text-[#4c4964]" id="instructionBlock">
					<p class="text-[#9a94c0]">Instructions will appear here when delivery starts.</p>
				</div>
				<img id="instrArt" src="{{ $mock('art-gamepad') }}" alt="" class="pointer-events-none absolute -bottom-2 -right-2 hidden w-[150px] select-none sm:block"
					style="mask-image:linear-gradient(135deg,transparent 2%,black 30%);-webkit-mask-image:linear-gradient(135deg,transparent 2%,black 30%);" draggable="false">
			</div>

			{{-- D) What's next? (delivered) --}}
			<div id="whatsNextCard" class="hidden rounded-2xl border border-[#e9e5f7] bg-white p-6 shadow-[0_8px_24px_rgba(38,24,98,.05)]">
				<h2 class="text-[16px] font-extrabold text-[#17142b]">What's next?</h2>
				<ul class="mt-4 space-y-3.5 text-[12.5px] text-[#6d6a86]">
					<li class="flex items-start gap-3"><img src="{{ $mock('todo-security') }}" alt="" class="mt-0.5 h-[26px] w-[26px] shrink-0 select-none rounded-[8px]"><span><b class="block text-[13px] font-extrabold text-[#17142b]">Keep your account secure</b>Do not share your account details with anyone.</span></li>
					<li class="flex items-start gap-3"><img src="{{ $mock('todo-doc') }}" alt="" class="mt-0.5 h-[26px] w-[26px] shrink-0 select-none rounded-[8px]"><span><b class="block text-[13px] font-extrabold text-[#17142b]">Having issues?</b>Contact our support team, we are here to help.</span></li>
					<li class="flex items-start gap-3"><img src="{{ $mock('todo-game') }}" alt="" class="mt-0.5 h-[26px] w-[26px] shrink-0 select-none rounded-[8px]"><span><b class="block text-[13px] font-extrabold text-[#17142b]">Leave a review</b>Share your experience and help other players.</span></li>
				</ul>
				<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="mt-4 flex h-[40px] w-full items-center justify-center gap-2 rounded-[10px] bg-[#4627ee] text-[13.5px] font-extrabold text-white shadow-[0_10px_24px_rgba(70,39,238,.28)] hover:bg-[#3b1fd6]">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg> Contact support</a>
			</div>
		</div>

		<p class="mt-7 text-center text-[13px] text-[#6d6a86]">Need help? <a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="font-extrabold text-[#5535fc] hover:underline">Contact the seller.</a></p>
	</div>

	<script>
		(() => {
			const root = document.getElementById('orderApp');
			const statusUrl = root.dataset.statusUrl;
			const codeUrl = root.dataset.codeUrl;
			const csrf = document.querySelector('meta[name="csrf-token"]').content;
			const el = (id) => document.getElementById(id);

			const TERMINAL = ['connected', 'expired', 'cancelled'];
			const CONN_FORM_STATES = ['account_assigned', 'waiting_for_connection_code', 'connection_failed'];
			const CONN_PROGRESS_STATES = ['connection_code_submitted', 'operator_connecting'];
			const CONN_VISIBLE_STATES = [...CONN_FORM_STATES, ...CONN_PROGRESS_STATES, 'locked_24h'];
			const IN_DELIVERY = ['account_assigned', 'waiting_for_connection_code', 'connection_code_submitted', 'operator_connecting', 'connection_failed', 'locked_24h'];

			let pollingMs = 8000, pollTimer = null, lastPayload = null, items = [], activeId = null;

			const fmtDate = (iso) => { if (!iso) return null; const d = new Date(iso); return isNaN(d) ? null : d.toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' }); };
			const maskEmail = (e) => { if (!e || !e.includes('@')) return e || ''; const [u, d] = e.split('@'); return (u.slice(0, 2) + '***') + '@' + d; };

			function bannerInfo(status) {
				switch (status) {
					case 'connected': return ['Delivered 🎉', 'Your game account is ready! Follow the instructions below to access your game.', 'thanks'];
					case 'new':
					case 'waiting_for_operator': return ['Processing', 'We are working on your order and preparing your game.', 'eta'];
					case 'expired': return ['Link expired', 'This link has expired. Please request a new delivery link from the seller.', null];
					case 'cancelled': return ['Order cancelled', 'Please contact the seller about this order.', null];
					default: return ['In Delivery', 'We are preparing your access. Follow the steps below.', 'eta'];
				}
			}

			function activeStep(status) {
				if (status === 'connected') return 4;
				if (IN_DELIVERY.includes(status)) return 3;
				if (status === 'expired' || status === 'cancelled') return 0;
				return 2;
			}

			const STEPS = [
				{ name: 'Order received', caption: root.dataset.created || '' },
				{ name: 'Processing', caption: 'Operator is working on your order' },
				{ name: 'In Delivery', caption: 'Delivering your order' },
				{ name: 'Delivered', caption: 'You will receive your game' },
			];
			const IMG = { check: root.dataset.stepCheck, gear: root.dataset.stepGear, truck: root.dataset.stepTruck, box: root.dataset.stepBox };
			const PENDING_IMG = [null, IMG.gear, IMG.truck, IMG.box];

			function renderStepper(status) {
				const cur = activeStep(status);
				const wrap = el('stepper');
				wrap.classList.remove('hidden');
				wrap.replaceChildren();

				const row = document.createElement('div');
				row.className = 'flex items-center px-6';
				const labels = document.createElement('div');
				labels.className = 'mt-2 grid grid-cols-4';

				STEPS.forEach((s, i) => {
					const n = i + 1;
					const done = cur >= 4 ? true : n < cur;
					const active = n === cur && cur < 4;
					const img = document.createElement('img');
					img.className = 'h-11 w-11 shrink-0 select-none rounded-full' + (active && n > 2 ? ' ring-2 ring-[#5b3df5] ring-offset-2' : '');
					img.src = done ? IMG.check : (active ? (n === 2 ? IMG.gear : PENDING_IMG[i]) : (PENDING_IMG[i] || IMG.check));
					img.alt = s.name;
					row.append(img);
					if (n < 4) {
						const line = document.createElement('div');
						line.className = 'mx-2 h-[2.5px] flex-1 rounded ' + (n < cur || cur >= 4 ? 'bg-[#5b3df5]' : 'bg-[#e2ddf3]');
						row.append(line);
					}
					const lab = document.createElement('div');
					lab.className = 'text-center';
					const t = document.createElement('div');
					t.className = 'text-[13px] font-extrabold ' + (active ? 'text-[#5535fc]' : (done || cur >= 4 ? 'text-[#17142b]' : 'text-[#8a86a8]'));
					t.textContent = s.name;
					const c = document.createElement('div');
					c.className = 'mt-0.5 text-[11px] leading-tight text-[#9a94c0]';
					c.textContent = s.caption;
					lab.append(t, c);
					labels.append(lab);
				});
				wrap.replaceChildren(row, labels);
			}

			function renderHappening(status) {
				const list = el('happeningList');
				list.replaceChildren();
				const steps = [
					'We verify your order',
					'We prepare your account',
					'We deliver it to you securely',
					'You receive access and instructions',
				];
				// before assignment: verifying done, preparing active
				const activeIdx = 1;
				steps.forEach((txt, i) => {
					const li = document.createElement('li');
					li.className = 'flex items-center gap-3';
					const dot = document.createElement('span');
					dot.className = 'flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full ' +
						(i < activeIdx ? 'bg-[#5b3df5]' : i === activeIdx ? 'bg-white ring-2 ring-[#5b3df5]' : 'bg-[#e9e5f7]');
					if (i < activeIdx) dot.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.4" class="h-2.5 w-2.5"><polyline points="20 6 9 17 4 12"/></svg>';
					if (i === activeIdx) dot.innerHTML = '<span class="h-2 w-2 rounded-full bg-[#5b3df5]"></span>';
					const t = document.createElement('span');
					t.className = 'text-[13px] ' + (i === activeIdx ? 'font-extrabold text-[#5535fc]' : i < activeIdx ? 'font-semibold text-[#211d3a]' : 'font-semibold text-[#9a94c0]');
					t.textContent = txt;
					li.append(dot, t);
					list.append(li);
				});
			}

			function copyBtn(value) {
				const b = document.createElement('button');
				b.type = 'button'; b.dataset.copy = value;
				b.className = 'copy-btn shrink-0 rounded-lg p-1.5 hover:bg-[#eceafe]';
				b.title = 'Copy';
				b.innerHTML = '<img src="{{ $mock('ic-copy') }}" class="h-[16px] w-[16px]" alt="Copy">';
				return b;
			}

			function credRow(label, value, { mask = false, hint = null } = {}) {
				const row = document.createElement('div');
				row.className = 'rounded-[10px] bg-[#f4f2fb]';
				const top = document.createElement('div');
				top.className = 'flex items-center gap-2 px-3 py-2';
				const main = document.createElement('div');
				main.className = 'min-w-0 flex-1';
				const l = document.createElement('div'); l.className = 'text-[9.5px] font-bold uppercase tracking-[.07em] text-[#8a86a8]'; l.textContent = label;
				const v = document.createElement('div'); v.className = 'truncate font-mono text-[13px] font-semibold text-[#17142b]';
				let revealed = !mask;
				const masked = () => '•'.repeat(Math.max(8, Math.min(14, (value || '').length)));
				v.textContent = revealed ? value : masked();
				main.append(l, v);
				top.append(main);
				if (mask) {
					const eye = document.createElement('button');
					eye.type = 'button'; eye.title = 'Show'; eye.className = 'shrink-0 rounded-lg p-1.5 text-[#8a86a8] hover:bg-[#eceafe]';
					eye.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
					eye.addEventListener('click', () => { revealed = !revealed; v.textContent = revealed ? value : masked(); });
					top.append(eye);
				}
				top.append(copyBtn(value));
				row.append(top);
				if (hint) { const h = document.createElement('div'); h.className = 'px-3 pb-2 text-[11px] text-[#9a94c0]'; h.textContent = hint; row.append(h); }
				return row;
			}

			function renderAccount(item) {
				const has = !!item.account;
				el('accountCard').classList.toggle('hidden', !has);
				el('whatsNextCard').classList.toggle('hidden', !has);
				el('happeningCard').classList.toggle('hidden', has);
				el('todoCard').classList.toggle('hidden', has);
				el('instrArt').src = has ? '{{ $mock('art-guide') }}' : '{{ $mock('art-gamepad') }}';
				if (!has) { renderHappening(item.status); return; }
				const block = el('accountBlock');
				block.replaceChildren();
				block.append(credRow('Email / Username', item.account.login || '', {}));
				const isFake = item.account.password_type === 'fake';
				block.append(credRow('Password', item.account.password || '', {
					mask: true,
					hint: isFake ? 'Use these details to start the console connection. Final sign-in is completed after you send the code.' : null,
				}));
			}

			function renderInstruction(item) {
				const block = el('instructionBlock');
				block.replaceChildren();
				if (item.instruction && (item.instruction.title || item.instruction.body)) {
					if (item.instruction.title) { const h = document.createElement('div'); h.className = 'mb-1 font-extrabold text-[#17142b]'; h.textContent = item.instruction.title; block.append(h); }
					const body = document.createElement('div'); body.className = 'whitespace-pre-wrap'; body.textContent = item.instruction.body || ''; block.append(body);
				} else {
					const p = document.createElement('p'); p.className = 'text-[#9a94c0]'; p.textContent = 'Instructions will appear here when delivery starts.'; block.append(p);
				}
				if (item.tutorial_url) {
					const a = document.createElement('a');
					a.href = item.tutorial_url; a.target = '_blank'; a.rel = 'noopener noreferrer';
					a.className = 'mt-3 inline-flex items-center gap-2 rounded-[10px] border border-[#e2dcf6] bg-white px-4 py-2 text-[12.5px] font-extrabold text-[#4627ee] hover:bg-[#faf8ff]';
					a.textContent = 'Open installation tutorial';
					block.append(a);
				}
			}

			function renderConnection(item) {
				const c = item.connection || {};
				const show = c.required && CONN_VISIBLE_STATES.includes(item.status);
				el('connectionCard').classList.toggle('hidden', !show);
				if (!show) return;
				const inForm = CONN_FORM_STATES.includes(item.status);
				const inProgress = CONN_PROGRESS_STATES.includes(item.status);
				const locked = item.status === 'locked_24h';
				el('connForm').classList.toggle('hidden', !inForm);
				el('connProgress').classList.toggle('hidden', !inProgress);
				el('connProgress').classList.toggle('flex', inProgress);
				el('connLocked').classList.toggle('hidden', !locked);
				el('connLocked').classList.toggle('flex', locked);
				const used = c.attempts_used || 0, limit = c.attempts_limit || 0;
				el('attemptsText').textContent = limit ? `Attempts used: ${used} of ${limit}` : '';
				const noAttempts = limit && used >= limit;
				const sub = el('connSubmit'); sub.disabled = !!noAttempts;
				sub.querySelector('.btn-label').textContent = noAttempts ? 'No attempts left' : 'Send code';
				if (inProgress) el('connProgressText').textContent = item.status === 'operator_connecting'
					? 'Operator is connecting your console. Please keep this page open.'
					: 'We received your code. Waiting for an operator to connect your console.';
				if (locked) { const until = fmtDate(c.locked_until); el('connLockedText').textContent = 'Too many attempts.' + (until ? ' Try again after ' + until + '.' : '') + ' An operator may grant extra attempts.'; }
			}

			function tabDot(status) {
				if (['connected', 'account_assigned'].includes(status)) return 'bg-emerald-500';
				if (['connection_failed', 'locked_24h'].includes(status)) return 'bg-rose-500';
				return 'bg-[#5b3df5]';
			}

			function renderTabs() {
				const bar = el('tabBar');
				bar.replaceChildren();
				if (items.length <= 1) { bar.classList.add('hidden'); bar.classList.remove('flex'); return; }
				bar.classList.remove('hidden'); bar.classList.add('flex');
				items.forEach((it, i) => {
					const active = String(it.id) === String(activeId);
					const b = document.createElement('button');
					b.type = 'button';
					b.className = 'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-[13px] font-extrabold transition ' +
						(active ? 'border-transparent bg-[#4627ee] text-white shadow-[0_8px_20px_rgba(70,39,238,.3)]' : 'border-[#e2dcf6] bg-white text-[#211d3a] hover:border-[#c9befa]');
					const dot = document.createElement('span'); dot.className = 'h-2 w-2 rounded-full ' + (active ? 'bg-white' : tabDot(it.status));
					const label = document.createElement('span'); label.textContent = it.game || ('Game ' + (i + 1));
					b.append(dot, label);
					b.addEventListener('click', () => { activeId = it.id; if (lastPayload) render(lastPayload); });
					bar.append(b);
				});
			}

			const activeItem = () => items.find((i) => String(i.id) === String(activeId)) || items[0];
			const legacyItem = (p) => ({ id: 0, game: p.game, platform: p.platform, status: p.status, account: p.account, connection: p.connection, instruction: p.instruction, tutorial_url: p.tutorial_url });

			function render(payload) {
				if (!payload || !payload.status) return;
				lastPayload = payload;
				pollingMs = Math.max(3000, (payload.polling_interval_seconds || 8) * 1000);
				items = Array.isArray(payload.items) && payload.items.length ? payload.items : [legacyItem(payload)];
				if (activeId === null || !items.some((i) => String(i.id) === String(activeId))) activeId = items[0].id;

				el('orderNumber').textContent = payload.order_number || '';
				el('dOrderNumber').textContent = payload.order_number || '';
				el('dEmail').textContent = payload.customer_email ? maskEmail(payload.customer_email) : '—';

				renderTabs();
				const item = activeItem();

				const [big, sub, etaMode] = bannerInfo(item.status);
				el('bannerBig').textContent = big;
				el('bannerSub').textContent = sub;
				const eta = el('bannerEta');
				eta.classList.toggle('hidden', !etaMode);
				if (etaMode === 'eta') el('bannerEtaText').innerHTML = 'Estimated time: <b class="text-[#5535fc]">5 – 30 minutes</b>';
				if (etaMode === 'thanks') el('bannerEtaText').textContent = 'Thank you for choosing us!';
				el('bannerArt').src = item.status === 'connected' ? root.dataset.artDelivered : root.dataset.artProcessing;

				renderStepper(item.status);

				el('dPlatform').textContent = item.platform || payload.platform || '';
				el('dGame').textContent = item.game || '—';

				renderAccount(item);
				renderInstruction(item);
				renderConnection(item);
			}

			// copy (delegated)
			document.addEventListener('click', async (e) => {
				const btn = e.target.closest('.copy-btn');
				if (!btn) return;
				const value = btn.dataset.copy || '';
				try {
					if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(value);
					else { const ta = document.createElement('textarea'); ta.value = value; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.append(ta); ta.select(); document.execCommand('copy'); ta.remove(); }
					btn.classList.add('bg-[#e6f9ef]');
					setTimeout(() => btn.classList.remove('bg-[#e6f9ef]'), 1200);
				} catch (_) {}
			});

			async function refresh() {
				try {
					const res = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
					if (res.ok) { render(await res.json()); if (items.length && items.every((i) => TERMINAL.includes(i.status))) return; }
				} catch (_) {}
				pollTimer = setTimeout(refresh, pollingMs);
			}

			el('connForm').addEventListener('submit', async (e) => {
				e.preventDefault();
				const connError = el('connError'); connError.classList.add('hidden');
				const connInput = el('connection_code'), connSubmit = el('connSubmit');
				const code = (connInput.value || '').trim().toUpperCase();
				if (!/^[A-Za-z0-9]{6,8}$/.test(code)) { el('connErrorText').textContent = 'Code must be 6–8 letters or digits.'; connError.classList.remove('hidden'); connError.classList.add('flex'); return; }
				connSubmit.disabled = true;
				const label = connSubmit.querySelector('.btn-label'); const prev = label.textContent; label.textContent = 'Sending…';
				try {
					const res = await fetch(codeUrl, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ connection_code: code, item: activeId }) });
					const data = await res.json().catch(() => ({}));
					if (data.order) { connInput.value = ''; render(data.order); }
					if (!data.ok) { el('connErrorText').textContent = data.message || 'Unable to send code. Please try again.'; connError.classList.remove('hidden'); connError.classList.add('flex'); }
				} catch (_) { el('connErrorText').textContent = 'Network error. Please try again.'; connError.classList.remove('hidden'); connError.classList.add('flex'); }
				finally { label.textContent = prev; connSubmit.disabled = false; }
			});

			render(@json($payload));
			pollTimer = setTimeout(refresh, pollingMs);
		})();
	</script>
</x-delivery.site>

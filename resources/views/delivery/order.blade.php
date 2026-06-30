<x-delivery.site :title="'Order ' . $payload['order_number']">
	<div class="mx-auto max-w-5xl"
		id="orderApp"
		data-status-url="{{ route('delivery.order.status', ['token' => $order->token]) }}"
		data-code-url="{{ route('delivery.order.connection-code.store', ['token' => $order->token]) }}"
		data-delivered-art="{{ asset('site/banner-delivered-blue.webp') }}"
		data-processing-art="{{ asset('site/banner-processing-blue.webp') }}">

		{{-- Top bar --}}
		<div class="mb-4 flex items-center justify-between gap-3">
			<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-[#0b6bff]">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
				Back to store
			</a>
			<span class="text-sm font-semibold text-slate-500">Order #<span id="orderNumber">{{ $payload['order_number'] }}</span></span>
		</div>

		{{-- Status banner --}}
		<div class="relative overflow-hidden rounded-3xl border border-white bg-gradient-to-r from-[#eaf6ff] via-[#d6e9ff] to-[#bcdcff] p-6 shadow-sm sm:p-8">
			<div class="relative z-10 max-w-md">
				<p class="text-sm font-semibold text-[#0b6bff]/80">Your order is</p>
				<h1 id="bannerBig" class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Processing</h1>
				<p id="bannerSub" class="mt-3 text-sm leading-relaxed text-slate-600"></p>
				<p id="bannerEta" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-[#0b6bff]">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
					<span id="bannerEtaText">Estimated time: 5 – 30 minutes</span>
				</p>
			</div>
			<img id="bannerArt" src="{{ asset('site/banner-processing-blue.webp') }}" alt=""
				class="pointer-events-none absolute right-0 top-1/2 hidden h-[115%] -translate-y-1/2 select-none object-contain sm:block" draggable="false">
		</div>

		{{-- Stepper --}}
		<div id="stepper" class="mt-6 grid grid-cols-4 gap-2"></div>

		{{-- Tabs (multi-game) --}}
		<div class="mt-6 hidden flex-wrap gap-2" id="tabBar"></div>

		{{-- Info cards --}}
		<div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
			@php
				$infoCard = fn($label, $id, $val = '') => '';
			@endphp
			<div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
				<div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg> Platform</div>
				<div class="mt-1.5 font-bold text-slate-900" id="dPlatform">{{ $payload['platform'] }}</div>
			</div>
			<div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
				<div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><rect x="2" y="6" width="20" height="12" rx="5"/><line x1="7" y1="11" x2="10" y2="11"/><line x1="8.5" y1="9.5" x2="8.5" y2="12.5"/></svg> Game</div>
				<div class="mt-1.5 truncate font-bold text-slate-900" id="dGame">—</div>
			</div>
			<div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
				<div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M7 14h6"/></svg> Order number</div>
				<div class="mt-1.5 truncate font-bold text-slate-900" id="dOrderNumber">{{ $payload['order_number'] }}</div>
			</div>
			<div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
				<div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/></svg> Order placed</div>
				<div class="mt-1.5 font-bold text-slate-900">{{ $order->created_at?->format('d M Y') }}<span class="block text-xs font-medium text-slate-500">at {{ $order->created_at?->format('H:i:s') }}</span></div>
			</div>
			<div class="col-span-2 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:col-span-1">
				<div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg> Email</div>
				<div class="mt-1.5 truncate font-bold text-slate-900" id="dEmail">—</div>
			</div>
		</div>

		{{-- Account details --}}
		<div class="mt-6 hidden rounded-3xl border border-white bg-white p-6 shadow-sm" id="accountCard">
			<div class="flex items-center gap-2">
				<h2 class="text-lg font-bold text-slate-900">Your account details</h2>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 text-emerald-500"><path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/></svg>
			</div>
			<p class="mt-1 text-sm text-slate-500">Use the credentials below to log in to your account.</p>
			<div class="mt-4 space-y-2" id="accountBlock"></div>
			<div class="mt-4 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-3 text-sm text-rose-700">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
				<span><strong>IMPORTANT.</strong> Do not change the account login or password. Doing so may cause a temporary loss of access to the game. If you have any questions, contact the seller.</span>
			</div>
		</div>

		{{-- Connection code (QR platforms) --}}
		<section class="mt-6 hidden rounded-3xl border border-white bg-white p-6 shadow-sm" id="connectionCard">
			<h2 class="text-lg font-bold text-slate-900">Console connection</h2>
			<p class="mt-1 text-sm text-slate-500" id="connDesc">Enter the code shown on your console screen to finish the connection.</p>

			<form id="connForm" class="mt-4 hidden">
				@csrf
				<label for="connection_code" class="text-sm font-semibold text-slate-700">Connection code</label>
				<input id="connection_code" name="connection_code" maxlength="8" minlength="6" autocomplete="off" autocapitalize="characters" spellcheck="false"
					placeholder="6–8 characters"
					class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm uppercase tracking-[0.12em] outline-none focus:border-[#0b6bff] focus:ring-2 focus:ring-[#cfe3fb]">
				<p class="mt-2 text-xs text-slate-500" id="attemptsText"></p>
				<div class="mt-3 hidden items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700" id="connError">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
					<span id="connErrorText"></span>
				</div>
				<button type="submit" id="connSubmit" class="mt-3 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#0b8dff] to-[#005be0] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-200 transition hover:opacity-95 disabled:opacity-60">
					<span class="btn-label">Send code</span>
				</button>
			</form>

			<div class="mt-4 hidden items-start gap-2 rounded-xl border border-[#bcdcff] bg-[#eaf3ff] px-3 py-2.5 text-sm text-[#0b6bff]" id="connProgress">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
				<span id="connProgressText"></span>
			</div>
			<div class="mt-4 hidden items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700" id="connLocked">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
				<span id="connLockedText"></span>
			</div>
		</section>

		{{-- Instructions + What's next --}}
		<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
			<div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
				<h2 class="text-lg font-bold text-slate-900">Instructions</h2>
				<div class="mt-3 text-sm leading-relaxed text-slate-600" id="instructionBlock">
					<p class="text-slate-400">Instructions will appear here when delivery starts.</p>
				</div>
			</div>
			<div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
				<h2 class="text-lg font-bold text-slate-900">What's next?</h2>
				<ul class="mt-3 space-y-3 text-sm text-slate-600">
					<li class="flex items-start gap-2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500"><path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5z"/></svg><span><b class="text-slate-900">Keep your account secure</b><br>Do not share your account details with anyone.</span></li>
					<li class="flex items-start gap-2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-5 w-5 shrink-0 text-[#0b6bff]"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg><span><b class="text-slate-900">Having issues?</b><br>Contact our support team, we are here to help.</span></li>
				</ul>
				<a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#0b8dff] to-[#005be0] px-4 py-3 text-sm font-bold text-white shadow-md">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
					Contact support
				</a>
			</div>
		</div>
	</div>

	<script>
		(() => {
			const root = document.getElementById('orderApp');
			const statusUrl = root.dataset.statusUrl;
			const codeUrl = root.dataset.codeUrl;
			const csrf = document.querySelector('meta[name="csrf-token"]').content;
			const el = (id) => document.getElementById(id);

			const STEPS = ['Order received', 'Processing', 'In Delivery', 'Delivered'];
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
					case 'connected': return ['Delivered 🎉', 'Your game account is ready! Follow the instructions below to access your game.', null];
					case 'new':
					case 'waiting_for_operator': return ['Processing', 'We are working on your order and preparing your game.', 'Estimated time: 5 – 30 minutes'];
					case 'expired': return ['Link expired', 'This link has expired. Please request a new delivery link from the seller.', null];
					case 'cancelled': return ['Order cancelled', 'Please contact the seller about this order.', null];
					default: return ['In Delivery', 'We are preparing your access. Follow the steps below.', 'Estimated time: 5 – 30 minutes'];
				}
			}

			function activeStep(status) {
				if (status === 'connected') return 4;
				if (IN_DELIVERY.includes(status)) return 3;
				if (status === 'expired' || status === 'cancelled') return 0;
				return 2;
			}

			function renderStepper(status) {
				const cur = activeStep(status);
				const wrap = el('stepper');
				wrap.replaceChildren();
				STEPS.forEach((name, i) => {
					const n = i + 1;
					const done = cur >= 4 ? true : n < cur;
					const active = n === cur;
					const col = document.createElement('div');
					col.className = 'flex flex-col items-center text-center';
					const circle = document.createElement('div');
					circle.className = 'flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold ' +
						(done ? 'bg-[#0b6bff] text-white' : active ? 'bg-[#0b6bff] text-white ring-4 ring-[#cfe3fb]' : 'bg-slate-100 text-slate-400');
					circle.innerHTML = done
						? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="h-4 w-4"><polyline points="20 6 9 17 4 12"/></svg>'
						: String(n);
					const label = document.createElement('div');
					label.className = 'mt-1.5 text-[11px] font-semibold leading-tight ' + (done || active ? 'text-slate-800' : 'text-slate-400');
					label.textContent = name;
					col.append(circle, label);
					wrap.append(col);
				});
			}

			function copyBtn(value) {
				const b = document.createElement('button');
				b.type = 'button'; b.dataset.copy = value;
				b.className = 'copy-btn shrink-0 rounded-lg border border-slate-200 bg-white p-2 text-slate-500 hover:bg-slate-50';
				b.title = 'Copy';
				b.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>';
				return b;
			}

			function credRow(label, value, { mask = false, hint = null } = {}) {
				const row = document.createElement('div');
				row.className = 'rounded-xl border border-slate-200 bg-slate-50/60';
				const top = document.createElement('div');
				top.className = 'flex items-center gap-2 px-3 py-2.5';
				const main = document.createElement('div');
				main.className = 'min-w-0 flex-1';
				const l = document.createElement('div'); l.className = 'text-[11px] font-semibold uppercase tracking-wide text-slate-400'; l.textContent = label;
				const v = document.createElement('div'); v.className = 'truncate font-mono text-sm text-slate-900';
				let revealed = !mask;
				const masked = () => '•'.repeat(Math.max(6, Math.min(14, (value || '').length)));
				v.textContent = revealed ? value : masked();
				main.append(l, v);
				top.append(main);
				if (mask) {
					const eye = document.createElement('button');
					eye.type = 'button'; eye.title = 'Show'; eye.className = 'shrink-0 rounded-lg border border-slate-200 bg-white p-2 text-slate-500 hover:bg-slate-50';
					eye.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
					eye.addEventListener('click', () => { revealed = !revealed; v.textContent = revealed ? value : masked(); });
					top.append(eye);
				}
				top.append(copyBtn(value));
				row.append(top);
				if (hint) { const h = document.createElement('div'); h.className = 'px-3 pb-2.5 text-xs text-slate-500'; h.textContent = hint; row.append(h); }
				return row;
			}

			function renderAccount(item) {
				const card = el('accountCard');
				const block = el('accountBlock');
				block.replaceChildren();
				if (!item.account) { card.classList.add('hidden'); return; }
				card.classList.remove('hidden');
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
					if (item.instruction.title) { const h = document.createElement('div'); h.className = 'mb-1 font-bold text-slate-900'; h.textContent = item.instruction.title; block.append(h); }
					const body = document.createElement('div'); body.className = 'whitespace-pre-wrap'; body.textContent = item.instruction.body || ''; block.append(body);
				} else {
					const p = document.createElement('p'); p.className = 'text-slate-400'; p.textContent = 'Instructions will appear here when delivery starts.'; block.append(p);
				}
				if (item.tutorial_url) {
					const a = document.createElement('a');
					a.href = item.tutorial_url; a.target = '_blank'; a.rel = 'noopener noreferrer';
					a.className = 'mt-4 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50';
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
				if (status === 'waiting_for_connection_code') return 'bg-[#38bdf8]';
				if (['connection_failed', 'locked_24h'].includes(status)) return 'bg-rose-500';
				if (['connection_code_submitted', 'operator_connecting', 'waiting_for_operator', 'new'].includes(status)) return 'bg-[#0b6bff]';
				return 'bg-slate-400';
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
					b.className = 'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition ' +
						(active ? 'border-transparent bg-gradient-to-r from-[#0b8dff] to-[#005be0] text-white shadow' : 'border-slate-200 bg-white text-slate-700 hover:border-[#9cc7f7]');
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

				const [big, sub, eta] = bannerInfo(item.status);
				el('bannerBig').textContent = big;
				el('bannerSub').textContent = sub;
				el('bannerEta').classList.toggle('hidden', !eta);
				if (eta) el('bannerEtaText').textContent = eta;
				el('bannerArt').src = item.status === 'connected' ? root.dataset.deliveredArt : root.dataset.processingArt;

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
					btn.classList.add('text-emerald-600', 'border-emerald-300');
					setTimeout(() => btn.classList.remove('text-emerald-600', 'border-emerald-300'), 1500);
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

<x-dynamic-component component="delivery.layout" title="Order {{ $payload['order_number'] }}">
	<div id="orderApp"
		data-status-url="{{ route('delivery.order.status', ['token' => $order->token]) }}"
		data-code-url="{{ route('delivery.order.connection-code.store', ['token' => $order->token]) }}">

		{{-- Header --}}
		<div class="card">
			<div class="card-grad-header" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
				<div>
					<h1>Order #<span id="orderNumber">{{ $payload['order_number'] }}</span></h1>
					<p id="platformLine">{{ $payload['platform'] }}</p>
				</div>
				<span class="badge badge--muted" id="statusBadge"><span class="dot"></span><span class="badge-text">…</span></span>
			</div>

			<div class="card-body">
				{{-- State banner --}}
				<div class="alert alert--info hidden" id="stateBanner" style="margin-bottom:16px;">
					<span class="ic" id="bannerIcon"></span>
					<span><strong id="bannerTitle"></strong><br><span id="bannerText"></span></span>
				</div>

				{{-- Order details --}}
				<div class="details">
					<div class="detail"><div class="k">Order number</div><div class="v" id="dOrderNumber">{{ $payload['order_number'] }}</div></div>
					<div class="detail"><div class="k">Platform</div><div class="v" id="dPlatform">{{ $payload['platform'] }}</div></div>
					<div class="detail" id="dGameWrap"><div class="k">Game</div><div class="v" id="dGame"></div></div>
					<div class="detail" id="dEmailWrap"><div class="k">Email</div><div class="v" id="dEmail"></div></div>
					<div class="detail" id="dExpiresWrap"><div class="k">Link valid until</div><div class="v" id="dExpires"></div></div>
				</div>
			</div>
		</div>

		{{-- Account + Instruction --}}
		<div class="card">
			<div class="card-body">
				<h2>Account</h2>
				<div id="accountBlock" class="stack">
					<p class="muted">Loading…</p>
				</div>
			</div>
			<div class="card-body">
				<details class="accordion" id="instructionWrap" open>
					<summary><span>Instructions</span><span class="chev">▾</span></summary>
					<div id="instructionBlock" class="acc-body">
						<p class="muted">Instructions will appear here when delivery starts.</p>
					</div>
				</details>
			</div>
		</div>

		{{-- Connection code --}}
		<section class="card hidden" id="connectionCard">
			<div class="card-body">
				<h2>Console connection</h2>
				<p class="muted" id="connDesc">Enter the code shown on your console screen to finish the connection.</p>

				<form id="connForm" class="hidden" style="margin-top:14px;">
					@csrf
					<div class="form-row" style="margin-bottom:8px;">
						<label for="connection_code">Connection code</label>
						<input id="connection_code" name="connection_code" maxlength="8" minlength="6"
							autocomplete="off" autocapitalize="characters" spellcheck="false"
							placeholder="6–8 characters" style="letter-spacing:.12em;text-transform:uppercase;">
					</div>
					<p class="muted" id="attemptsText" style="margin:0 0 12px;font-size:13px;"></p>
					<div class="alert alert--danger hidden" id="connError" style="margin-bottom:12px;">
						<span class="ic"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span><span id="connErrorText"></span>
					</div>
					<button type="submit" class="btn" id="connSubmit"><span class="btn-label">Send code</span></button>
				</form>

				<div class="alert alert--info hidden" id="connProgress" style="margin-top:14px;">
					<span class="ic"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg></span><span id="connProgressText"></span>
				</div>
				<div class="alert alert--danger hidden" id="connLocked" style="margin-top:14px;">
					<span class="ic"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span><span id="connLockedText"></span>
				</div>
			</div>
		</section>
	</div>

	<script>
		(() => {
			const root = document.getElementById('orderApp');
			const statusUrl = root.dataset.statusUrl;
			const codeUrl = root.dataset.codeUrl;
			const csrf = document.querySelector('meta[name="csrf-token"]').content;

			const el = (id) => document.getElementById(id);
			const statusBadge = el('statusBadge');
			const accountBlock = el('accountBlock');
			const instructionBlock = el('instructionBlock');
			const instructionWrap = el('instructionWrap');
			const connectionCard = el('connectionCard');
			const connForm = el('connForm');
			const connInput = el('connection_code');
			const connSubmit = el('connSubmit');
			const attemptsText = el('attemptsText');
			const connError = el('connError');
			const connProgress = el('connProgress');
			const connLocked = el('connLocked');

			const BADGE = {
				new: ['Waiting for operator', 'badge--info badge--pulse'],
				waiting_for_operator: ['Waiting for operator', 'badge--info badge--pulse'],
				account_assigned: ['Account ready', 'badge--ok'],
				waiting_for_connection_code: ['Enter connection code', 'badge--accent'],
				connection_code_submitted: ['Code received', 'badge--info'],
				operator_connecting: ['Operator connecting', 'badge--info badge--pulse'],
				connected: ['Connected', 'badge--ok'],
				connection_failed: ['Connection failed', 'badge--danger'],
				locked_24h: ['Locked', 'badge--danger'],
				expired: ['Link expired', 'badge--muted'],
				cancelled: ['Cancelled', 'badge--muted'],
			};

			const S = (paths) => '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + paths + '</svg>';
			const ICON = {
				clock: S('<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/>'),
				check: S('<circle cx="12" cy="12" r="9"/><polyline points="8 12 11 15 16 9"/>'),
				bolt: S('<polygon points="13 2 4 14 11 14 11 22 20 10 13 10 13 2"/>'),
				mail: S('<rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/>'),
				plug: S('<path d="M9 2v6M15 2v6"/><path d="M6 8h12v3a6 6 0 0 1-12 0V8Z"/><path d="M12 17v5"/>'),
				alert: S('<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>'),
				lock: S('<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'),
				xcircle: S('<circle cx="12" cy="12" r="9"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/>'),
				info: S('<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>'),
			};

			const TERMINAL = ['connected', 'expired', 'cancelled'];
			const CONN_FORM_STATES = ['account_assigned', 'waiting_for_connection_code', 'connection_failed'];
			const CONN_PROGRESS_STATES = ['connection_code_submitted', 'operator_connecting'];
			const CONN_VISIBLE_STATES = [...CONN_FORM_STATES, ...CONN_PROGRESS_STATES, 'locked_24h'];

			let pollingMs = 8000;
			let pollTimer = null;

			function fmtDate(iso) {
				if (!iso) return null;
				const d = new Date(iso);
				if (isNaN(d)) return null;
				return d.toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' });
			}

			function setBanner(cls, icon, title, text) {
				const banner = el('stateBanner');
				if (!title && !text) { banner.classList.add('hidden'); return; }
				banner.className = 'alert ' + cls;
				banner.style.marginBottom = '16px';
				el('bannerIcon').innerHTML = ICON[icon] || ICON.info;
				el('bannerTitle').textContent = title || '';
				el('bannerText').textContent = text || '';
			}

			function bannerFor(data) {
				const c = data.connection || {};
				switch (data.status) {
					case 'new':
					case 'waiting_for_operator':
						return ['alert--info', 'clock', 'Waiting for operator',
							'Your order is being checked. Keep this page open — it updates automatically.'];
					case 'account_assigned':
						return ['alert--ok', 'check', 'Account assigned', 'Your account details are ready below.'];
					case 'waiting_for_connection_code':
						return ['alert--accent', 'bolt', 'Action needed',
							'Enter the code shown on your console to finish the connection.'];
					case 'connection_code_submitted':
						return ['alert--info', 'mail', 'Code received',
							'We received your code. An operator will connect your console shortly.'];
					case 'operator_connecting':
						return ['alert--info', 'plug', 'Operator is connecting your console', 'Please keep this page open.'];
					case 'connected':
						return ['alert--ok', 'check', 'Connected', 'Your console has been connected successfully.'];
					case 'connection_failed':
						return ['alert--danger', 'alert', 'Connection failed',
							'The last attempt did not go through. You can try again below.'];
					case 'locked_24h': {
						const until = fmtDate(c.locked_until);
						return ['alert--danger', 'lock', 'Temporarily locked',
							'Too many attempts.' + (until ? ' Try again after ' + until + '.' : '') +
							' An operator may grant extra attempts.'];
					}
					case 'expired':
						return ['alert--muted', 'clock', 'This link has expired',
							'Please request a new delivery link from the seller.'];
					case 'cancelled':
						return ['alert--muted', 'xcircle', 'Order cancelled', 'Contact support.'];
					default:
						return ['alert--info', 'info', '', ''];
				}
			}

			function credRow(label, value, hint) {
				const row = document.createElement('div');
				const main = document.createElement('div');
				main.className = 'cred-main';
				const l = document.createElement('div'); l.className = 'cred-label'; l.textContent = label;
				const v = document.createElement('div'); v.className = 'cred-value'; v.textContent = value;
				main.append(l, v);
				const btn = document.createElement('button');
				btn.type = 'button'; btn.className = 'copy-btn'; btn.textContent = 'Copy';
				btn.dataset.copy = value;
				const cred = document.createElement('div'); cred.className = 'cred';
				cred.append(main, btn);
				const wrap = document.createElement('div');
				wrap.append(cred);
				if (hint) { const h = document.createElement('div'); h.className = 'field-hint'; h.textContent = hint; wrap.append(h); }
				row.append(wrap);
				return row.firstChild;
			}

			function renderAccount(data) {
				accountBlock.replaceChildren();
				if (!data.account) {
					const p = document.createElement('p');
					p.className = 'muted';
					p.textContent = TERMINAL.includes(data.status) && data.status !== 'connected'
						? 'No account is attached to this order.'
						: 'Waiting for operator to assign an account…';
					accountBlock.append(p);
					return;
				}
				accountBlock.append(credRow('Login', data.account.login || '', null));
				const isFake = data.account.password_type === 'fake';
				const hint = isFake
					? 'Use these details to start the console connection. Final sign-in is completed after you send the code.'
					: null;
				accountBlock.append(credRow('Password', data.account.password || '', hint));
			}

			function renderInstruction(data) {
				instructionBlock.replaceChildren();
				if (data.instruction && (data.instruction.title || data.instruction.body)) {
					if (data.instruction.title) {
						const h = document.createElement('h3'); h.textContent = data.instruction.title;
						instructionBlock.append(h);
					}
					const body = document.createElement('div');
					body.textContent = data.instruction.body || '';
					instructionBlock.append(body);
				} else {
					const p = document.createElement('p'); p.className = 'muted';
					p.textContent = 'Instructions will appear here when delivery starts.';
					instructionBlock.append(p);
				}
			}

			function renderConnection(data) {
				const c = data.connection || {};
				const show = c.required && CONN_VISIBLE_STATES.includes(data.status);
				connectionCard.classList.toggle('hidden', !show);
				if (!show) return;

				const inForm = CONN_FORM_STATES.includes(data.status);
				const inProgress = CONN_PROGRESS_STATES.includes(data.status);
				const locked = data.status === 'locked_24h';

				connForm.classList.toggle('hidden', !inForm);
				connProgress.classList.toggle('hidden', !inProgress);
				connLocked.classList.toggle('hidden', !locked);

				const used = c.attempts_used || 0, limit = c.attempts_limit || 0;
				attemptsText.textContent = limit ? `Attempts used: ${used} of ${limit}` : '';
				const noAttempts = limit && used >= limit;
				connSubmit.disabled = !!noAttempts;
				connSubmit.querySelector('.btn-label').textContent = noAttempts ? 'No attempts left' : 'Send code';

				if (inProgress) {
					el('connProgressText').textContent = data.status === 'operator_connecting'
						? 'Operator is connecting your console. Please keep this page open.'
						: 'We received your code. Waiting for an operator to connect your console.';
				}
				if (locked) {
					const until = fmtDate(c.locked_until);
					el('connLockedText').textContent = 'Too many attempts.' +
						(until ? ' Try again after ' + until + '.' : '') + ' An operator may grant extra attempts.';
				}
			}

			function render(data) {
				if (!data || !data.status) return;
				pollingMs = Math.max(3000, (data.polling_interval_seconds || 8) * 1000);

				const [badgeText, badgeCls] = BADGE[data.status] || [String(data.status).replaceAll('_', ' '), 'badge--muted'];
				statusBadge.className = 'badge ' + badgeCls;
				statusBadge.innerHTML = '<span class="dot"></span>';
				const t = document.createElement('span'); t.className = 'badge-text'; t.textContent = badgeText;
				statusBadge.append(t);

				setBanner(...bannerFor(data));

				el('orderNumber').textContent = data.order_number || '';
				el('platformLine').textContent = [data.platform, data.game].filter(Boolean).join(' · ');
				el('dOrderNumber').textContent = data.order_number || '';
				el('dPlatform').textContent = data.platform || '';

				el('dGameWrap').classList.toggle('hidden', !data.game);
				el('dGame').textContent = data.game || '';
				el('dEmailWrap').classList.toggle('hidden', !data.customer_email);
				el('dEmail').textContent = data.customer_email || '';
				const exp = fmtDate(data.expires_at);
				el('dExpiresWrap').classList.toggle('hidden', !exp);
				el('dExpires').textContent = exp || '';

				renderAccount(data);
				renderInstruction(data);
				renderConnection(data);
			}

			// --- copy buttons (event delegation) ---
			document.addEventListener('click', async (e) => {
				const btn = e.target.closest('.copy-btn');
				if (!btn) return;
				const value = btn.dataset.copy || '';
				try {
					if (navigator.clipboard && window.isSecureContext) {
						await navigator.clipboard.writeText(value);
					} else {
						const ta = document.createElement('textarea');
						ta.value = value; ta.style.position = 'fixed'; ta.style.opacity = '0';
						document.body.append(ta); ta.select(); document.execCommand('copy'); ta.remove();
					}
					btn.classList.add('copied');
					const prev = btn.textContent; btn.textContent = 'Copied';
					setTimeout(() => { btn.classList.remove('copied'); btn.textContent = prev; }, 1600);
				} catch (_) {
					btn.textContent = 'Press Ctrl+C';
				}
			});

			// --- polling ---
			async function refresh() {
				try {
					const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
					if (res.ok) {
						const data = await res.json();
						render(data);
						if (TERMINAL.includes(data.status)) return; // stop polling on terminal states
					}
				} catch (_) { /* keep polling on transient errors */ }
				pollTimer = setTimeout(refresh, pollingMs);
			}

			// --- connection code submit ---
			connForm.addEventListener('submit', async (e) => {
				e.preventDefault();
				connError.classList.add('hidden');
				const code = (connInput.value || '').trim().toUpperCase();
				if (!/^[A-Za-z0-9]{6,8}$/.test(code)) {
					el('connErrorText').textContent = 'Code must be 6–8 letters or digits.';
					connError.classList.remove('hidden');
					return;
				}
				connSubmit.disabled = true;
				const label = connSubmit.querySelector('.btn-label');
				const prevLabel = label.textContent;
				label.textContent = 'Sending…';
				connSubmit.insertAdjacentHTML('afterbegin', '<span class="spinner"></span>');
				try {
					const res = await fetch(codeUrl, {
						method: 'POST',
						headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
						body: JSON.stringify({ connection_code: code }),
					});
					const data = await res.json().catch(() => ({}));
					if (data.order) {
						connInput.value = '';
						render(data.order);
					}
					if (!data.ok) {
						el('connErrorText').textContent = data.message || 'Unable to send code. Please try again.';
						connError.classList.remove('hidden');
					}
				} catch (_) {
					el('connErrorText').textContent = 'Network error. Please try again.';
					connError.classList.remove('hidden');
				} finally {
					const sp = connSubmit.querySelector('.spinner'); if (sp) sp.remove();
					label.textContent = prevLabel;
					connSubmit.disabled = false;
				}
			});

			// initial state from server payload, then start polling
			render(@json($payload));
			pollTimer = setTimeout(refresh, pollingMs);
		})();
	</script>
</x-dynamic-component>

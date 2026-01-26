<x-layouts.webapp>
<div class="app-shell">

	<div class="tabs-rail">
		<div class="tabs-wrap">
			<ul class="nav nav-tabs">
				<li class="nav-item">
					<button id="tabIssue" class="nav-link active" type="button">Выдача</button>
				</li>
				<li class="nav-item">
					<button id="tabHistory" class="nav-link" type="button">История</button>
				</li>
			</ul>
		</div>
	</div>

	<div class="tab-content">
		<div id="issueSection" class="tab-pane show active">
			<div class="card-panel">
				<div class="tab-header">
					<div class="tab-icon-wrap">🎮</div>
					<h2 class="tab-title">Выдача</h2>
					<div class="tab-subtitle">Заявка и быстрый доступ к аккаунтам</div>
				</div>

				<div class="card-section">
					<div id="moderationNotice" class="alert alert-warning d-none">
						Ваш аккаунт на модерации. Доступ появится после подтверждения админом.
					</div>
					<div class="row g-2">
						<div class="col-12">
							<label class="form-label" for="orderId">Номер заказа</label>
							<input id="orderId" class="form-control" type="text" placeholder="Номер заказа">
						</div>
						<div class="col-6">
							<label class="form-label" for="qty">Количество</label>
							<input id="qty" class="form-control" type="number" min="1" max="2" placeholder="Количество" value="1">
						</div>
						<div class="col-6">
							<label class="form-label" for="platform">Платформа</label>
							<input id="platform" class="form-control" type="text" placeholder="steam / xbox" value="steam">
						</div>
						<div class="col-12">
							<label class="form-label" for="game">Игра</label>
							<input id="game" class="form-control" type="text" placeholder="cs2 / minecraft" value="cs2">
						</div>
					</div>

					<div class="d-flex flex-wrap gap-2 mt-3">
						<button id="issueBtn" class="btn btn-primary" type="button">Выдать</button>
					</div>
				</div>

				<div id="issueResult" class="card-section d-none">
					<pre id="issueResultText" class="codebox"></pre>
				</div>
			</div>
		</div>

		<div id="historySection" class="tab-pane" style="display:none;">
			<div class="card-panel">
				<div class="tab-header">
					<div class="tab-icon-wrap">🧾</div>
					<h2 class="tab-title">История</h2>
					<div class="tab-subtitle">Последние выдачи и действия</div>
				</div>

				<div class="card-section">
					<div class="d-flex align-items-center gap-2 mb-2 history-controls">
						<button id="refreshHistoryBtn" class="btn btn-outline-secondary btn-sm" type="button">Обновить</button>
						<span id="historyStatus" class="small text-muted"></span>
						<div class="ms-auto d-flex align-items-center gap-2">
							<span class="small text-muted">Лимит</span>
							<select id="historyLimit" class="form-select form-select-sm">
								<option value="20">20</option>
								<option value="50">50</option>
								<option value="100">100</option>
							</select>
						</div>
					</div>
					<div id="historyList" class="list-stack">
						<div class="list-empty">Нет данных</div>
					</div>
					<div class="d-flex align-items-center justify-content-between mt-3">
						<div id="historyCount" class="small text-muted"></div>
						<div class="d-flex align-items-center gap-2">
							<button id="historyPrev" class="btn btn-outline-secondary btn-sm" type="button">Назад</button>
							<span id="historyPageInfo" class="small text-muted">Стр. 1/1</span>
							<button id="historyNext" class="btn btn-outline-secondary btn-sm" type="button">Вперед</button>
						</div>
					</div>
				</div>

				<div class="card-section">
					<div class="fw-semibold mb-2">STOLEN аккаунты, закреплённые за вами</div>
					<div id="stolenList" class="list-stack">
						<div class="list-empty">Нет данных</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="copyright" class="text-center small my-3">@accesshub_123_bot</div>

<script>
	(function () {
		const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
		const issueBtn = document.getElementById('issueBtn');
		const issueResult = document.getElementById('issueResult');
		const issueResultText = document.getElementById('issueResultText');
		const moderationNotice = document.getElementById('moderationNotice');
		const tabIssue = document.getElementById('tabIssue');
		const tabHistory = document.getElementById('tabHistory');
		const issueSection = document.getElementById('issueSection');
		const historySection = document.getElementById('historySection');
		const refreshHistoryBtn = document.getElementById('refreshHistoryBtn');
		const historyStatus = document.getElementById('historyStatus');
		const historyList = document.getElementById('historyList');
		const stolenList = document.getElementById('stolenList');
		const historyLimit = document.getElementById('historyLimit');
		const historyPrev = document.getElementById('historyPrev');
		const historyNext = document.getElementById('historyNext');
		const historyPageInfo = document.getElementById('historyPageInfo');
		const historyCount = document.getElementById('historyCount');

		let isAdmin = false;
		let isBootstrapped = false;
		let isActive = true;
		let historyPage = 1;
		let historyPageLimit = 20;
		let historyTotal = 0;
		if (historyLimit) {
			historyPageLimit = parseInt(historyLimit.value, 10) || 20;
		}

		function switchTab(tab) {
			if (tab === 'history') {
				issueSection.style.display = 'none';
				historySection.style.display = 'block';
				tabIssue.classList.remove('active');
				tabHistory.classList.add('active');
			} else {
				historySection.style.display = 'none';
				issueSection.style.display = 'block';
				tabHistory.classList.remove('active');
				tabIssue.classList.add('active');
			}
		}

		tabIssue.addEventListener('click', () => switchTab('issue'));
		tabHistory.addEventListener('click', () => {
			switchTab('history');
			loadHistory();
			loadStolen();
		});

		if (tg) {
			tg.ready();
			tg.expand();
			tg.setBackgroundColor('#0F1722');
			tg.setHeaderColor('#28323A');

			const user = tg.initDataUnsafe?.user;
			void user;
		}

		async function apiPost(url, payload) {
			const res = await fetch(url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
				},
				body: JSON.stringify(payload || {}),
				credentials: 'same-origin',
			});

			const text = await res.text();
			return { status: res.status, text };
		}

		async function apiGet(url) {
			const res = await fetch(url, {
				method: 'GET',
				headers: {
					'Accept': 'application/json',
				},
				credentials: 'same-origin',
			});

			const data = await res.json().catch(() => null);
			return { status: res.status, data };
		}

		async function bootstrap() {
			if (!tg || !tg.initData) {
				return false;
			}

			const result = await apiPost('/webapp/bootstrap', { initData: tg.initData });

			if (result.status === 204) {
				return true;
			}

			return false;
		}

		async function loadMe() {
			const resp = await apiGet('/webapp/api/me');
			if (resp.status === 200 && resp.data) {
				isAdmin = resp.data.role === 'admin';
				isActive = resp.data.is_active === true;
				if (!isActive && moderationNotice) {
					moderationNotice.classList.remove('d-none');
					if (issueBtn) issueBtn.disabled = true;
				}
				return true;
			}
			return false;
		}

		async function apiPostJson(url, payload) {
			const res = await fetch(url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
					'Accept': 'application/json',
				},
				body: JSON.stringify(payload || {}),
				credentials: 'same-origin',
			});

			const data = await res.json().catch(() => null);
			return { status: res.status, data };
		}

		function flashHistoryStatus(text) {
			if (!historyStatus) return;
			historyStatus.textContent = text || '';
			if (text) {
				setTimeout(() => {
					if (historyStatus.textContent === text) {
						historyStatus.textContent = '';
					}
				}, 3000);
			}
		}

		function setButtonLoading(button, isLoading) {
			if (!button) return;
			if (isLoading) {
				if (button.dataset.loading === '1') return;
				button.dataset.loading = '1';
				button.dataset.prevHtml = button.innerHTML;
				button.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>';
				button.disabled = true;
			} else {
				if (button.dataset.loading !== '1') return;
				button.disabled = false;
				button.innerHTML = button.dataset.prevHtml || button.textContent;
				delete button.dataset.loading;
				delete button.dataset.prevHtml;
			}
		}

		async function runAction(button, url, payload) {
			if (!isBootstrapped) {
				flashHistoryStatus('Не инициализировано. Обновите WebApp.');
				return;
			}
			if (!isActive) {
				flashHistoryStatus('Аккаунт на модерации.');
				return;
			}
			setButtonLoading(button, true);

			const resp = await apiPostJson(url, payload);
			const message = resp.data?.message || (resp.status === 200 ? 'Готово' : 'Ошибка');
			flashHistoryStatus(message);

			setButtonLoading(button, false);
		}

		function formatIssuedAt(value) {
			if (!value) return '-';
			const parsed = new Date(value.replace(' ', 'T'));
			if (Number.isNaN(parsed.getTime())) {
				return value;
			}
			const pad = (num) => String(num).padStart(2, '0');
			return `${pad(parsed.getDate())}.${pad(parsed.getMonth() + 1)}.${String(parsed.getFullYear()).slice(-2)} ${pad(parsed.getHours())}:${pad(parsed.getMinutes())}`;
		}

		function renderIssueResult(payload) {
			const items = payload?.items || [];
			if (items.length === 0) {
				issueResultText.textContent = payload?.message || 'Выдача выполнена.';
				return;
			}
			if (items.length === 1) {
				issueResultText.textContent = `Логин: ${items[0].login}\nПароль: ${items[0].password}`;
				return;
			}
			const lines = items.map((item, index) => `#${index + 1}\nЛогин: ${item.login}\nПароль: ${item.password}`);
			issueResultText.textContent = lines.join('\n\n');
		}

		issueBtn.addEventListener('click', async () => {
			if (!isActive) {
				issueResult.classList.remove('d-none');
				issueResultText.textContent = 'Аккаунт на модерации. Доступ появится после подтверждения админом.';
				return;
			}
			const orderId = document.getElementById('orderId').value.trim();
			const platform = document.getElementById('platform').value.trim();
			const game = document.getElementById('game').value.trim();
			const qtyRaw = document.getElementById('qty').value.trim();
			const qty = qtyRaw === '' ? 0 : Math.max(1, Math.min(2, parseInt(qtyRaw, 10)));

			if (!orderId || !platform || !game || qty <= 0) {
				issueResult.classList.remove('d-none');
				issueResultText.textContent = 'Заполните все поля.';
				return;
			}

			issueResult.classList.remove('d-none');
			issueResultText.textContent = 'Отправка запроса...';
			setButtonLoading(issueBtn, true);

			const resp = await apiPostJson('/webapp/api/issue', {
				order_id: orderId,
				platform,
				game,
				qty,
			});

			if (resp.status === 200 && resp.data?.ok) {
				if (resp.data?.show_in_webapp) {
					renderIssueResult(resp.data);
				} else {
					issueResultText.textContent = resp.data?.message || 'Отправлено в чат.';
				}
				setButtonLoading(issueBtn, false);
				return;
			}

			if (resp.status === 403) {
				issueResultText.textContent = 'Не инициализировано. Закройте и откройте WebApp ещё раз.';
				setButtonLoading(issueBtn, false);
				return;
			}

			issueResultText.textContent = resp.data?.error || 'Ошибка выдачи. Проверьте данные.';
			setButtonLoading(issueBtn, false);
		});

		function buildProblemButtons(item) {
			const wrap = document.createElement('div');
			wrap.className = 'd-flex flex-wrap gap-2';

			const btn = (label, url, payload) => {
				const b = document.createElement('button');
				b.type = 'button';
				b.className = 'btn btn-outline-secondary btn-sm';
				b.textContent = label;
				b.addEventListener('click', () => runAction(b, url, payload));
				wrap.appendChild(b);
			};

			btn('Неверный пароль', '/webapp/api/problem', { account_id: item.account_id, reason: 'wrong_password' });
			btn('Нет доступа к почте', '/webapp/api/problem', { account_id: item.account_id, reason: 'no_email' });
			btn('Аккаунт заблокирован / Не пускает', '/webapp/api/problem', { account_id: item.account_id, reason: 'blocked' });
			btn('Украден', '/webapp/api/problem', { account_id: item.account_id, reason: 'stolen' });
			if (isAdmin) {
				btn('Мёртвый', '/webapp/api/problem', { account_id: item.account_id, reason: 'dead' });
			}

			const passBtn = document.createElement('button');
			passBtn.type = 'button';
			passBtn.className = 'btn btn-outline-secondary btn-sm';
			passBtn.textContent = 'Обновить пароль';
			passBtn.addEventListener('click', () => {
				const newPass = prompt('Введите новый пароль');
				if (!newPass) return;
				runAction(passBtn, '/webapp/api/update-password', { account_id: item.account_id, password: newPass });
			});
			wrap.appendChild(passBtn);

			return wrap;
		}

		function renderHistory(items) {
			historyList.innerHTML = '';
			if (!items || items.length === 0) {
				const empty = document.createElement('div');
				empty.className = 'list-empty';
				empty.textContent = 'Нет данных';
				historyList.appendChild(empty);
				return;
			}

			items.forEach(item => {
				const card = document.createElement('div');
				card.className = 'list-card';

				const accountId = item.account_id ? `#${item.account_id}` : '-';
				const login = item.login || '-';
				const issuedAt = formatIssuedAt(item.issued_at);
				const qtyValue = item.qty ? `x${item.qty}` : '-';

				card.innerHTML = `
					<div class="list-row">
						<div class="list-label">Заказ</div>
						<div class="list-value">${item.order_id || '-'}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Игра</div>
						<div class="list-value">${item.game || '-'}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Платформа</div>
						<div class="list-value">${item.platform || '-'}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Количество</div>
						<div class="list-value"><span class="chip">${qtyValue}</span></div>
					</div>
					<div class="list-row">
						<div class="list-label">Аккаунт</div>
						<div class="list-value">${accountId}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Логин</div>
						<div class="list-value"><code>${login}</code></div>
					</div>
					<div class="list-row">
						<div class="list-label">Выдано</div>
						<div class="list-value">${issuedAt}</div>
					</div>
				`;

				const actions = document.createElement('div');
				actions.className = 'list-actions';
				actions.appendChild(buildProblemButtons(item));
				card.appendChild(actions);
				historyList.appendChild(card);
			});
		}

		function updateHistoryPager() {
			const total = Number.isFinite(historyTotal) ? historyTotal : 0;
			const limit = Number.isFinite(historyPageLimit) ? historyPageLimit : 20;
			const pages = Math.max(1, Math.ceil(total / limit));
			const page = Math.min(historyPage, pages);

			if (historyPageInfo) {
				historyPageInfo.textContent = `Стр. ${page}/${pages}`;
			}
			if (historyCount) {
				if (total === 0) {
					historyCount.textContent = 'Нет данных';
				} else {
					const start = (page - 1) * limit + 1;
					const end = Math.min(page * limit, total);
					historyCount.textContent = `Показано ${start}-${end} из ${total}`;
				}
			}

			if (historyPrev) historyPrev.disabled = page <= 1;
			if (historyNext) historyNext.disabled = page >= pages;
		}

		function renderStolen(items) {
			stolenList.innerHTML = '';
			if (!items || items.length === 0) {
				const empty = document.createElement('div');
				empty.className = 'list-empty';
				empty.textContent = 'Нет данных';
				stolenList.appendChild(empty);
				return;
			}

			items.forEach(item => {
				const card = document.createElement('div');
				card.className = 'list-card';

				card.innerHTML = `
					<div class="list-row">
						<div class="list-label">Аккаунт</div>
						<div class="list-value">#${item.id}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Логин</div>
						<div class="list-value"><code>${item.login || '-'}</code></div>
					</div>
					<div class="list-row">
						<div class="list-label">Дедлайн</div>
						<div class="list-value">${item.deadline || '-'}</div>
					</div>
				`;

				const wrap = document.createElement('div');
				wrap.className = 'list-actions';

				const recoverBtn = document.createElement('button');
				recoverBtn.type = 'button';
				recoverBtn.className = 'btn btn-outline-secondary btn-sm';
				recoverBtn.textContent = 'Восстановлен';
				recoverBtn.addEventListener('click', () => {
					const newPass = prompt('Введите новый пароль');
					if (!newPass) return;
					runAction(recoverBtn, '/webapp/api/recover-stolen', { account_id: item.id, password: newPass });
				});

				const postponeBtn = document.createElement('button');
				postponeBtn.type = 'button';
				postponeBtn.className = 'btn btn-outline-secondary btn-sm';
				postponeBtn.textContent = 'Перенести на 1 день';
				postponeBtn.addEventListener('click', () => {
					runAction(postponeBtn, '/webapp/api/postpone-stolen', { account_id: item.id });
				});

				wrap.appendChild(recoverBtn);
				wrap.appendChild(postponeBtn);
				card.appendChild(wrap);
				stolenList.appendChild(card);
			});
		}

		async function loadHistory() {
			if (!isActive) {
				historyStatus.textContent = 'Аккаунт на модерации.';
				return;
			}
			historyStatus.textContent = 'Загрузка...';
			const resp = await apiGet(`/webapp/api/history?limit=${historyPageLimit}&page=${historyPage}`);
			if (resp.status === 403) {
				historyStatus.textContent = 'Не инициализировано.';
				return;
			}
			historyStatus.textContent = '';
			historyTotal = resp.data?.total ?? 0;
			renderHistory(resp.data?.items || []);
			updateHistoryPager();
		}

		async function loadStolen() {
			if (!isActive) {
				return;
			}
			const resp = await apiGet('/webapp/api/stolen');
			if (resp.status === 200) {
				renderStolen(resp.data?.items || []);
			}
		}

		refreshHistoryBtn.addEventListener('click', () => {
			loadHistory();
			loadStolen();
		});

		if (historyLimit) {
			historyLimit.addEventListener('change', () => {
				historyPageLimit = parseInt(historyLimit.value, 10) || 20;
				historyPage = 1;
				loadHistory();
			});
		}

		if (historyPrev) {
			historyPrev.addEventListener('click', () => {
				if (historyPage > 1) {
					historyPage -= 1;
					loadHistory();
				}
			});
		}

		if (historyNext) {
			historyNext.addEventListener('click', () => {
				const pages = Math.max(1, Math.ceil(historyTotal / historyPageLimit));
				if (historyPage < pages) {
					historyPage += 1;
					loadHistory();
				}
			});
		}

		async function init() {
			let ok = await loadMe();
			if (!ok) {
				ok = await bootstrap();
				if (ok) {
					await loadMe();
				}
			}
			isBootstrapped = ok;
		}

		init();
	})();
</script>
</x-layouts.webapp>


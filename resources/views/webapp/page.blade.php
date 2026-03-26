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
				<li class="nav-item">
					<button id="tabStolen" class="nav-link" type="button">В работе <span id="stolenBadge" class="d-none" style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;border-radius:999px;background:#e05c5c;color:#fff;font-size:10px;font-weight:700;padding:0 5px;margin-left:4px;vertical-align:middle"></span></button>
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
					<div id="browserNotice" class="d-none" style="background:rgba(36,129,201,.13);border:1px solid rgba(36,129,201,.35);border-radius:10px;padding:12px 14px;font-size:13px;line-height:1.5;margin-bottom:8px;">
						Вы открыли приложение в браузере. Для входа запросите ссылку командой <strong>/link</strong> в боте и откройте её.
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
							<div class="searchable-select-wrapper">
								<select id="platform" class="form-select searchable-select">
									<option value="">Выберите платформу...</option>
								</select>
							</div>
						</div>
						<div class="col-12">
							<label class="form-label" for="game">Игра</label>
							<div class="searchable-select-wrapper">
								<select id="game" class="form-select searchable-select">
									<option value="">Выберите игру...</option>
								</select>
							</div>
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
					<div class="fw-semibold mb-2">Поиск заказа</div>
					<div class="d-flex gap-2 mb-2">
						<input id="orderSearchInput" class="form-control" type="text" placeholder="Введите номер заказа...">
						<button id="orderSearchBtn" class="btn btn-primary btn-sm" type="button" style="white-space:nowrap">Найти</button>
					</div>
					<div id="orderSearchResult" class="list-stack"></div>
				</div>

			</div>
		</div>
		<div id="stolenSection" class="tab-pane" style="display:none;">
			<div class="card-panel">
				<div class="tab-header">
					<div class="tab-icon-wrap">🔒</div>
					<h2 class="tab-title">В работе</h2>
					<div class="tab-subtitle">STOLEN аккаунты, закреплённые за вами</div>
				</div>
				<div class="card-section">
					<span id="stolenStatus" class="small d-block mb-2" style="color:#a8d8a8;min-height:1.2em;"></span>
					<div id="stolenList" class="list-stack">
						<div class="list-empty">Нет данных</div>
					</div>
				</div>
			</div>
		</div>
		</div>
	</div>
	<div id="copyright" class="text-center small my-3">{{ '@accesshub_123_bot' }}</div>
</div>

<script>
	(function () {
		const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
		const issueBtn = document.getElementById('issueBtn');
		const issueResult = document.getElementById('issueResult');
		const issueResultText = document.getElementById('issueResultText');
		const moderationNotice = document.getElementById('moderationNotice');
		const tabIssue = document.getElementById('tabIssue');
		const tabHistory = document.getElementById('tabHistory');
		const tabStolen = document.getElementById('tabStolen');
		const stolenBadge = document.getElementById('stolenBadge');
		const issueSection = document.getElementById('issueSection');
		const historySection = document.getElementById('historySection');
		const stolenSection = document.getElementById('stolenSection');
		const refreshHistoryBtn = document.getElementById('refreshHistoryBtn');
		const historyStatus = document.getElementById('historyStatus');
		const historyList = document.getElementById('historyList');
		const stolenList = document.getElementById('stolenList');
		const stolenStatus = document.getElementById('stolenStatus');
		const historyLimit = document.getElementById('historyLimit');
		const historyPrev = document.getElementById('historyPrev');
		const historyNext = document.getElementById('historyNext');
		const historyPageInfo = document.getElementById('historyPageInfo');
		const historyCount = document.getElementById('historyCount');

		let isAdmin = false;
		let isBootstrapped = false;
		let isActive = true;
		let platformGames = {}; // platform → [game, ...]
		let allGames = [];      // все игры (для сброса)
		let historyPage = 1;
		let historyPageLimit = 20;
		let historyTotal = 0;
		if (historyLimit) {
			historyPageLimit = parseInt(historyLimit.value, 10) || 20;
		}

		function switchTab(tab) {
			issueSection.style.display = 'none';
			historySection.style.display = 'none';
			stolenSection.style.display = 'none';
			tabIssue.classList.remove('active');
			tabHistory.classList.remove('active');
			tabStolen.classList.remove('active');

			if (tab === 'history') {
				historySection.style.display = 'block';
				tabHistory.classList.add('active');
			} else if (tab === 'stolen') {
				stolenSection.style.display = 'block';
				tabStolen.classList.add('active');
			} else {
				issueSection.style.display = 'block';
				tabIssue.classList.add('active');
			}
		}

		tabIssue.addEventListener('click', () => switchTab('issue'));
		tabHistory.addEventListener('click', () => {
			switchTab('history');
			loadHistory();
		});
		tabStolen.addEventListener('click', () => {
			switchTab('stolen');
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
			console.log('apiPostJson: Sending request', { url, payload });
			try {
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

				console.log('apiPostJson: Response status', res.status);
				const data = await res.json().catch((e) => {
					console.error('apiPostJson: Failed to parse JSON', e);
					return null;
				});
				console.log('apiPostJson: Response data', data);
				return { status: res.status, data };
			} catch (e) {
				console.error('apiPostJson: Fetch error', e);
				return { status: 0, data: null, error: e.message };
			}
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

		function flashStolenStatus(text) {
			if (!stolenStatus) return;
			stolenStatus.textContent = text || '';
			if (text) {
				setTimeout(() => {
					if (stolenStatus.textContent === text) {
						stolenStatus.textContent = '';
					}
				}, 3000);
			}
		}

		function setButtonLoading(button, isLoading) {
			if (!button) return;
			const existingSpinner = button.querySelector('.btn-spinner');
			if (isLoading) {
				if (button.dataset.loading === '1') return;
				button.dataset.loading = '1';
				button.disabled = true;
				if (!existingSpinner) {
					const spinner = document.createElement('span');
					spinner.className = 'btn-spinner ms-2';
					spinner.setAttribute('aria-hidden', 'true');
					button.appendChild(spinner);
				}
			} else {
				if (button.dataset.loading !== '1') return;
				button.disabled = false;
				if (existingSpinner) {
					existingSpinner.remove();
				}
				delete button.dataset.loading;
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
			const platformSelect = document.getElementById('platform');
			const gameSelect = document.getElementById('game');
			
			// Get values directly from select elements - they should already be synced by selectOption()
			const platform = platformSelect ? platformSelect.value.trim() : '';
			const game = gameSelect ? gameSelect.value.trim() : '';
			const qtyRaw = document.getElementById('qty').value.trim();
			const qty = qtyRaw === '' ? 0 : Math.max(1, Math.min(2, parseInt(qtyRaw, 10)));

			console.log('Issue request data:', {
				orderId,
				platform,
				game,
				qty,
				platformSelectValue: platformSelect?.value,
				gameSelectValue: gameSelect?.value,
				platformSelectSelectedIndex: platformSelect?.selectedIndex,
				gameSelectSelectedIndex: gameSelect?.selectedIndex,
				platformSelectOptions: platformSelect ? Array.from(platformSelect.options).map((o, i) => ({index: i, value: o.value, text: o.text, selected: o.selected})) : [],
				gameSelectOptions: gameSelect ? Array.from(gameSelect.options).map((o, i) => ({index: i, value: o.value, text: o.text, selected: o.selected})) : [],
			});

			if (!orderId || !platform || !game || qty <= 0) {
				issueResult.classList.remove('d-none');
				issueResultText.textContent = 'Заполните все поля.';
				console.error('Validation failed:', {orderId, platform, game, qty});
				return;
			}

			issueResult.classList.remove('d-none');
			issueResultText.textContent = 'Отправка запроса...';
			setButtonLoading(issueBtn, true);

			const requestPayload = {
				order_id: orderId,
				platform,
				game,
				qty,
			};
			console.log('Sending request:', requestPayload);

			const resp = await apiPostJson('/webapp/api/issue', requestPayload);
			console.log('Response:', resp);

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

			// Handle different error scenarios
			let errorMessage = 'Ошибка выдачи. Проверьте данные.';
			if (resp.data?.error) {
				errorMessage = resp.data.error;
			} else if (resp.status >= 500) {
				errorMessage = 'Ошибка сервера. Попробуйте позже или обратитесь к администратору.';
			} else if (resp.status === 422 && resp.data) {
				errorMessage = resp.data.error || resp.data.message || 'Неверные данные.';
			} else if (!resp.data) {
				errorMessage = 'Ошибка соединения. Проверьте интернет и попробуйте снова.';
			}

			issueResultText.textContent = errorMessage;
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

			{
			const stolenBtn = document.createElement('button');
			stolenBtn.type = 'button';
			stolenBtn.className = 'btn btn-outline-secondary btn-sm';
			stolenBtn.textContent = 'Украден';
			stolenBtn.addEventListener('click', async () => {
				const resp = await apiPostJson('/webapp/api/problem', { account_id: item.account_id, reason: 'stolen' });
				const message = resp.data?.message || (resp.status === 200 ? 'Готово' : (resp.data?.error || 'Ошибка'));
				flashHistoryStatus(message);
				if (resp.status === 200) loadStolen();
			});
			wrap.appendChild(stolenBtn);
		}
			if (isAdmin) {
				btn('Мёртвый', '/webapp/api/problem', { account_id: item.account_id, reason: 'dead' });
			}

			const passBtn = document.createElement('button');
			passBtn.type = 'button';
			passBtn.className = 'btn btn-outline-secondary btn-sm';
			passBtn.textContent = 'Обновить пароль';

			const passInputWrap = document.createElement('div');
			passInputWrap.style.cssText = 'display:none;margin-top:8px;width:100%;';
			const histPassInput = document.createElement('input');
			histPassInput.type = 'password';
			histPassInput.className = 'form-control form-control-sm';
			histPassInput.placeholder = 'Новый пароль';
			histPassInput.style.marginBottom = '6px';
			const histPassConfirmBtn = document.createElement('button');
			histPassConfirmBtn.type = 'button';
			histPassConfirmBtn.className = 'btn btn-success btn-sm w-100';
			histPassConfirmBtn.textContent = 'Подтвердить';
			passInputWrap.appendChild(histPassInput);
			passInputWrap.appendChild(histPassConfirmBtn);

			passBtn.addEventListener('click', () => {
				passInputWrap.style.display = passInputWrap.style.display === 'none' ? 'block' : 'none';
				if (passInputWrap.style.display === 'block') histPassInput.focus();
			});
			histPassConfirmBtn.addEventListener('click', async () => {
				const newPass = histPassInput.value.trim();
				if (!newPass) { histPassInput.focus(); return; }
				const resp = await apiPostJson('/webapp/api/update-password', { account_id: item.account_id, password: newPass });
				const message = resp.data?.message || (resp.status === 200 ? 'Готово' : (resp.data?.error || 'Ошибка'));
				flashHistoryStatus(message);
			});
			wrap.appendChild(passBtn);
			wrap.appendChild(passInputWrap);

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
					<div class="list-row">
						<div class="list-label">Оператор</div>
						<div class="list-value">${item.operator || '-'}</div>
					</div>
					${item.comment ? `
					<div class="list-row">
						<div class="list-label">Комментарий</div>
						<div class="list-value">${item.comment}</div>
					</div>
					` : ''}
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

			// Обновляем бейдж
			if (stolenBadge) {
				if (items && items.length > 0) {
					stolenBadge.textContent = items.length;
					stolenBadge.classList.remove('d-none');
					stolenBadge.style.display = 'inline-flex';
				} else {
					stolenBadge.classList.add('d-none');
					stolenBadge.style.display = 'none';
				}
			}

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
					<div class="list-row">
						<div class="list-label">Оператор</div>
						<div class="list-value">${item.operator || '—'}</div>
					</div>
				`;

				if (item.is_mine) {
					const wrap = document.createElement('div');
					wrap.className = 'list-actions';

					const recoverBtn = document.createElement('button');
					recoverBtn.type = 'button';
					recoverBtn.className = 'btn btn-outline-secondary btn-sm';
					recoverBtn.textContent = 'Восстановлен';

					const passWrap = document.createElement('div');
					passWrap.style.cssText = 'display:none;margin-top:8px;';
					const passInput = document.createElement('input');
					passInput.type = 'password';
					passInput.className = 'form-control form-control-sm';
					passInput.placeholder = 'Новый пароль';
					passInput.style.marginBottom = '6px';
					const passConfirmBtn = document.createElement('button');
					passConfirmBtn.type = 'button';
					passConfirmBtn.className = 'btn btn-success btn-sm w-100';
					passConfirmBtn.textContent = 'Подтвердить';
					passWrap.appendChild(passInput);
					passWrap.appendChild(passConfirmBtn);

					recoverBtn.addEventListener('click', () => {
						passWrap.style.display = passWrap.style.display === 'none' ? 'block' : 'none';
						if (passWrap.style.display === 'block') passInput.focus();
					});

					passConfirmBtn.addEventListener('click', async () => {
						const newPass = passInput.value.trim();
						if (!newPass) { passInput.focus(); return; }
						const resp = await apiPostJson('/webapp/api/recover-stolen', { account_id: item.id, password: newPass });
						const message = resp.data?.message || (resp.status === 200 ? 'Готово' : (resp.data?.error || 'Ошибка'));
						flashStolenStatus(message);
						if (resp.status === 200) loadStolen();
					});

					const postponeBtn = document.createElement('button');
					postponeBtn.type = 'button';
					postponeBtn.className = 'btn btn-outline-secondary btn-sm';
					postponeBtn.textContent = 'Перенести на 1 день';
					postponeBtn.addEventListener('click', async () => {
						const resp = await apiPostJson('/webapp/api/postpone-stolen', { account_id: item.id });
						const message = resp.data?.message || (resp.status === 200 ? 'Перенесено' : (resp.data?.error || 'Ошибка'));
						flashStolenStatus(message);
						if (resp.status === 200) loadStolen();
					});

					wrap.appendChild(recoverBtn);
					wrap.appendChild(postponeBtn);
					card.appendChild(passWrap);
					card.appendChild(wrap);
				}
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

		function initSearchableSelect(selectId, showSearch = true) {
			const select = document.getElementById(selectId);
			if (!select) return;

			const wrapper = select.closest('.searchable-select-wrapper');
			if (!wrapper) return;

			// Создаём видимый триггер вместо нативного select
			const trigger = document.createElement('div');
			trigger.className = 'searchable-select-trigger';
			trigger.tabIndex = 0;
			trigger.innerHTML = `<span class="trigger-text trigger-placeholder">${select.options[0]?.text || 'Выберите...'}</span><span class="trigger-arrow">▼</span>`;
			wrapper.insertBefore(trigger, select);

			// Создаём dropdown
			const dropdown = document.createElement('div');
			dropdown.className = 'searchable-select-dropdown';

			let searchInput = null;
			if (showSearch) {
				const searchContainer = document.createElement('div');
				searchContainer.className = 'searchable-select-search';
				searchInput = document.createElement('input');
				searchInput.type = 'text';
				searchInput.placeholder = 'Поиск...';
				searchContainer.appendChild(searchInput);
				dropdown.appendChild(searchContainer);
			}

			const optionsContainer = document.createElement('div');
			optionsContainer.className = 'searchable-select-options';

			dropdown.appendChild(optionsContainer);
			wrapper.appendChild(dropdown);

			let options = [];
			let selectedIndex = -1;

			function updateOptions() {
				const currentValue = select.value; // Save current selection
				
				options = Array.from(select.options).map((opt, idx) => ({
					value: opt.value,
					text: opt.textContent,
					index: idx,
					element: null,
					selected: opt.value === currentValue
				}));

				optionsContainer.innerHTML = '';
				options.forEach((opt, idx) => {
					if (idx === 0 && opt.value === '') return; // Skip placeholder
					const optionEl = document.createElement('div');
					optionEl.className = 'searchable-select-option';
					if (opt.selected) {
						optionEl.classList.add('selected');
					}
					optionEl.textContent = opt.text;
					optionEl.dataset.value = opt.value;
					optionEl.dataset.index = idx;
					opt.element = optionEl;
					optionsContainer.appendChild(optionEl);
				});

				filterOptions('');
			}

			function filterOptions(query) {
				const q = query.toLowerCase().trim();
				let visibleCount = 0;
				let firstVisible = null;

				options.forEach((opt, idx) => {
					if (idx === 0 && opt.value === '') return;
					const matches = !q || opt.text.toLowerCase().includes(q);
					if (opt.element) {
						if (matches) {
							opt.element.classList.remove('hidden');
							if (visibleCount === 0) {
								firstVisible = opt.element;
								opt.element.classList.add('highlighted');
							} else {
								opt.element.classList.remove('highlighted');
							}
							visibleCount++;
						} else {
							opt.element.classList.add('hidden');
							opt.element.classList.remove('highlighted');
						}
					}
				});

				selectedIndex = firstVisible ? parseInt(firstVisible.dataset.index) : -1;
			}

			function selectOption(optionEl) {
				const value = optionEl.dataset.value;
				const text = optionEl.textContent;

				const matchingOption = Array.from(select.options).find(opt => opt.value === value);
				if (matchingOption) {
					select.selectedIndex = Array.from(select.options).indexOf(matchingOption);
					select.value = value;
				} else {
					select.value = value;
				}

				// Обновляем текст триггера
				const triggerText = trigger.querySelector('.trigger-text');
				if (triggerText) {
					triggerText.textContent = text;
					triggerText.classList.remove('trigger-placeholder');
				}

				options.forEach(opt => {
					if (opt.element) opt.element.classList.remove('selected');
				});
				if (optionEl) optionEl.classList.add('selected');

				select.dispatchEvent(new Event('change', { bubbles: true }));
				dropdown.classList.remove('active');
				if (searchInput) { searchInput.value = ''; }
				filterOptions('');
			}

			function highlightNext() {
				const visible = Array.from(optionsContainer.querySelectorAll('.searchable-select-option:not(.hidden)'));
				if (visible.length === 0) return;
				
				const current = optionsContainer.querySelector('.highlighted');
				let nextIndex = 0;
				
				if (current) {
					const currentIndex = visible.indexOf(current);
					nextIndex = (currentIndex + 1) % visible.length;
					current.classList.remove('highlighted');
				}
				
				visible[nextIndex].classList.add('highlighted');
				visible[nextIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
			}

			function highlightPrev() {
				const visible = Array.from(optionsContainer.querySelectorAll('.searchable-select-option:not(.hidden)'));
				if (visible.length === 0) return;
				
				const current = optionsContainer.querySelector('.highlighted');
				let nextIndex = visible.length - 1;
				
				if (current) {
					const currentIndex = visible.indexOf(current);
					nextIndex = currentIndex > 0 ? currentIndex - 1 : visible.length - 1;
					current.classList.remove('highlighted');
				}
				
				visible[nextIndex].classList.add('highlighted');
				visible[nextIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
			}

			// Открытие по клику/Enter на триггер
			function openDropdown() {
				dropdown.classList.add('active');
				updateOptions();
				if (searchInput) searchInput.focus();
				else optionsContainer.focus();
			}

			trigger.addEventListener('click', openDropdown);
			trigger.addEventListener('keydown', (e) => {
				if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDropdown(); }
				else if (e.key === 'Escape') { dropdown.classList.remove('active'); }
			});

			if (searchInput) {
				searchInput.addEventListener('input', (e) => filterOptions(e.target.value));
				searchInput.addEventListener('keydown', (e) => {
					if (e.key === 'ArrowDown') { e.preventDefault(); highlightNext(); }
					else if (e.key === 'ArrowUp') { e.preventDefault(); highlightPrev(); }
					else if (e.key === 'Enter') {
						e.preventDefault();
						const highlighted = optionsContainer.querySelector('.highlighted');
						if (highlighted) selectOption(highlighted);
					} else if (e.key === 'Escape') {
						dropdown.classList.remove('active');
						trigger.focus();
					}
				});
			}

			optionsContainer.addEventListener('click', (e) => {
				const optionEl = e.target.closest('.searchable-select-option');
				if (optionEl && !optionEl.classList.contains('hidden')) selectOption(optionEl);
			});

			// Закрытие по клику вне
			document.addEventListener('click', (e) => {
				if (!wrapper.contains(e.target)) dropdown.classList.remove('active');
			});

			// Обновление при изменении опций
			const observer = new MutationObserver(updateOptions);
			observer.observe(select, { childList: true });

			updateOptions();
		}

		function populateGameSelect(games) {
			const gameSelect = document.getElementById('game');
			if (!gameSelect) return;

			gameSelect.value = '';
			gameSelect.innerHTML = '<option value="">Выберите игру...</option>';
			games.forEach(game => {
				const opt = document.createElement('option');
				opt.value = game;
				opt.textContent = game;
				gameSelect.appendChild(opt);
			});

			const wrapper = gameSelect.parentElement;
			wrapper.querySelector('.searchable-select-dropdown')?.remove();
			wrapper.querySelector('.searchable-select-trigger')?.remove();
			initSearchableSelect('game');
		}

		async function loadPlatforms() {
			try {
				const resp = await apiGet('/webapp/api/schema');

				if (resp.status === 200 && resp.data?.tabs) {
					// Сохраняем маппинг платформа → игры
					platformGames = resp.data.platform_games || {};

					const issueTab = resp.data.tabs.find(tab => tab.id === 'issue');
					if (!issueTab?.fields) return;

					const platformField = issueTab.fields.find(field => field.name === 'platform');
					const gameField = issueTab.fields.find(field => field.name === 'game');

					// Сохраняем все игры для первоначального отображения
					allGames = (gameField?.options || []).map(o => o.value);

					// Заполняем платформы
					const platformSelect = document.getElementById('platform');
					if (platformSelect && platformField?.options?.length > 0) {
						platformSelect.innerHTML = '<option value="">Выберите платформу...</option>';
						platformField.options.forEach(option => {
							const opt = document.createElement('option');
							opt.value = option.value;
							opt.textContent = option.label;
							platformSelect.appendChild(opt);
						});
						const existingDropdown = platformSelect.parentElement.querySelector('.searchable-select-dropdown');
						if (existingDropdown) existingDropdown.remove();
						initSearchableSelect('platform', false);

						// Фильтруем игры при смене платформы
						platformSelect.addEventListener('change', () => {
							const selectedPlatform = platformSelect.value;
							const games = selectedPlatform && platformGames[selectedPlatform]
								? platformGames[selectedPlatform]
								: allGames;
							populateGameSelect(games);
						});
					}

					// Заполняем игры (изначально все)
					populateGameSelect(allGames);
				}
			} catch (e) {
				console.error('Failed to load platforms:', e);
			}
		}

		const orderSearchBtn = document.getElementById('orderSearchBtn');
		const orderSearchInput = document.getElementById('orderSearchInput');
		const orderSearchResult = document.getElementById('orderSearchResult');

		async function runOrderSearch() {
			const orderId = orderSearchInput?.value.trim();
			if (!orderId) return;

			setButtonLoading(orderSearchBtn, true);
			orderSearchResult.innerHTML = '';

			const resp = await apiGet(`/webapp/api/order-search?order_id=${encodeURIComponent(orderId)}`);
			setButtonLoading(orderSearchBtn, false);

			if (resp.status === 403) {
				orderSearchResult.innerHTML = '<div class="list-empty">Не инициализировано.</div>';
				return;
			}

			if (!resp.data?.found || !resp.data.items?.length) {
				orderSearchResult.innerHTML = '<div class="list-empty">Заказ не найден.</div>';
				return;
			}

			resp.data.items.forEach(item => {
				const card = document.createElement('div');
				card.className = 'list-card';
				card.style.border = item.is_mine
					? '1px solid rgba(36,129,201,.5)'
					: '1px solid rgba(255,255,255,.1)';

				card.innerHTML = `
					<div class="list-row">
						<div class="list-label">Заказ</div>
						<div class="list-value">${item.order_id}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Оператор</div>
						<div class="list-value">${item.is_mine ? '<span style="color:#2481C9;font-weight:600">Вы</span>' : item.operator}</div>
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
						<div class="list-label">Логин</div>
						<div class="list-value"><code>${item.login || '-'}</code></div>
					</div>
					<div class="list-row">
						<div class="list-label">Выдано</div>
						<div class="list-value">${formatIssuedAt(item.issued_at)}</div>
					</div>
				`;
				orderSearchResult.appendChild(card);
			});
		}

		orderSearchBtn?.addEventListener('click', runOrderSearch);
		orderSearchInput?.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') runOrderSearch();
		});

		async function init() {
			await loadPlatforms();

			let ok = await loadMe();
			if (!ok) {
				ok = await bootstrap();
				if (ok) {
					await loadMe();
				}
			}
			isBootstrapped = ok;

			if (isBootstrapped) {
				// Загружаем stolen сразу для показа бейджа
				loadStolen();
			}

			if (!isBootstrapped) {
				const notice = document.getElementById('browserNotice');
				if (notice) notice.classList.remove('d-none');
				if (issueBtn) issueBtn.disabled = true;
			}
		}

		init();
	})();
</script>
</x-layouts.webapp>


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
				<li class="nav-item">
					<button id="tabDelivery" class="nav-link" type="button">Доставка</button>
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
				<span class="d-none">steam / xbox cs2 / minecraft</span>
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
					<div class="fw-semibold mb-2">Поиск заказа</div>
					<div class="d-flex gap-2 mb-2">
						<input id="orderSearchInput" class="form-control" type="text" placeholder="Введите номер заказа...">
						<button id="orderSearchBtn" class="btn btn-primary btn-sm" type="button" style="white-space:nowrap">Найти</button>
					</div>
					<div id="orderSearchResult" class="list-stack"></div>
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
		<div id="deliverySection" class="tab-pane" style="display:none;">
			<div class="card-panel">
				<div class="tab-header">
					<div class="tab-icon-wrap">D</div>
					<h2 class="tab-title">Доставка</h2>
					<div class="tab-subtitle">Заказы, выдача аккаунта и подключение по коду</div>
				</div>
				<div class="card-section">
					<div class="d-flex gap-2 mb-2">
						<input id="deliverySearchInput" class="form-control" type="text" placeholder="Номер заказа, email, игра, код...">
						<button id="refreshDeliveryBtn" class="btn btn-primary btn-sm" type="button" style="white-space:nowrap">Обновить</button>
					</div>
					<span id="deliveryStatus" class="small d-block mb-2" style="color:#a8d8a8;min-height:1.2em;"></span>
					<div id="deliveryList" class="list-stack">
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
		const tabDelivery = document.getElementById('tabDelivery');
		const stolenBadge = document.getElementById('stolenBadge');
		const issueSection = document.getElementById('issueSection');
		const historySection = document.getElementById('historySection');
		const stolenSection = document.getElementById('stolenSection');
		const deliverySection = document.getElementById('deliverySection');
		const refreshHistoryBtn = document.getElementById('refreshHistoryBtn');
		const historyStatus = document.getElementById('historyStatus');
		const historyList = document.getElementById('historyList');
		const stolenList = document.getElementById('stolenList');
		const stolenStatus = document.getElementById('stolenStatus');
		const refreshDeliveryBtn = document.getElementById('refreshDeliveryBtn');
		const deliverySearchInput = document.getElementById('deliverySearchInput');
		const deliveryStatus = document.getElementById('deliveryStatus');
		const deliveryList = document.getElementById('deliveryList');
		const historyLimit = document.getElementById('historyLimit');
		const historyPrev = document.getElementById('historyPrev');
		const historyNext = document.getElementById('historyNext');
		const historyPageInfo = document.getElementById('historyPageInfo');
		const historyCount = document.getElementById('historyCount');

		let isAdmin = false;
		let userRole = null;
		let isDeliveryOperator = false;
		let isBootstrapped = false;
		let isActive = true;
		let platformGames = {}; // platform → [game, ...]
		let allGames = [];      // все игры (для сброса)
		let historyPage = 1;
		let historyPageLimit = 20;
		let historyTotal = 0;
		const initialParams = new URLSearchParams(window.location.search);
		const focusedDeliveryOrderId = initialParams.get('delivery_order') || initialParams.get('id') || '';
		if (historyLimit) {
			historyPageLimit = parseInt(historyLimit.value, 10) || 20;
		}

		function switchTab(tab) {
			issueSection.style.display = 'none';
			historySection.style.display = 'none';
			stolenSection.style.display = 'none';
			deliverySection.style.display = 'none';
			tabIssue.classList.remove('active');
			tabHistory.classList.remove('active');
			tabStolen.classList.remove('active');
			tabDelivery.classList.remove('active');
			let activeTab = tabIssue;

			if (tab === 'history') {
				historySection.style.display = 'block';
				tabHistory.classList.add('active');
				activeTab = tabHistory;
			} else if (tab === 'stolen') {
				stolenSection.style.display = 'block';
				tabStolen.classList.add('active');
				activeTab = tabStolen;
			} else if (tab === 'delivery') {
				deliverySection.style.display = 'block';
				tabDelivery.classList.add('active');
				activeTab = tabDelivery;
			} else {
				issueSection.style.display = 'block';
				tabIssue.classList.add('active');
			}

			requestAnimationFrame(() => {
				activeTab?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
			});
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
		tabDelivery.addEventListener('click', () => {
			switchTab('delivery');
			loadDeliveryOrders();
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
				userRole = resp.data.role;
				isDeliveryOperator = userRole === 'delivery_operator';
				isActive = resp.data.is_active === true;
				if (!isActive && moderationNotice) {
					moderationNotice.classList.remove('d-none');
					if (issueBtn) issueBtn.disabled = true;
				}
				if (isDeliveryOperator) {
					[tabIssue, tabHistory, tabStolen].forEach((tab) => {
						if (tab?.parentElement) tab.parentElement.style.display = 'none';
					});
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

		function escapeHtml(value) {
			const div = document.createElement('div');
			div.textContent = value == null ? '' : String(value);
			return div.innerHTML;
		}

		function flashDeliveryStatus(text) {
			if (!deliveryStatus) return;
			deliveryStatus.textContent = text || '';
			if (text) {
				setTimeout(() => {
					if (deliveryStatus.textContent === text) {
						deliveryStatus.textContent = '';
					}
				}, 3000);
			}
		}

		function fillSelect(select, values, selectedValue = '') {
			if (!select) return;
			select.innerHTML = '';
			const placeholder = document.createElement('option');
			placeholder.value = '';
			placeholder.textContent = 'Выберите...';
			select.appendChild(placeholder);

			const normalizedValues = Array.isArray(values) ? values : [];
			normalizedValues.forEach((item) => {
				const value = typeof item === 'string' ? item : item?.name;
				if (!value) return;

				const option = document.createElement('option');
				option.value = value;
				option.textContent = typeof item === 'string'
					? item
					: `${item.name}${item.accounts_count ? ` (${item.accounts_count})` : ''}`;
				if (value === selectedValue) option.selected = true;
				select.appendChild(option);
			});

			if (selectedValue && !Array.from(select.options).some((option) => option.value === selectedValue)) {
				const option = document.createElement('option');
				option.value = selectedValue;
				option.textContent = selectedValue;
				option.selected = true;
				select.appendChild(option);
			}
		}

		// Wrap a dynamically created select into the searchable widget
		// (type-to-filter dropdown, same as on the issue tab).
		function wrapSearchable(select) {
			const wrapper = document.createElement('div');
			wrapper.className = 'searchable-select-wrapper mb-2';
			select.classList.add('searchable-select');
			select.classList.remove('mb-2');
			wrapper.appendChild(select);
			attachSearchableSelect(select, true);
			return wrapper;
		}

		function fillAccountSelect(select, accounts, selectedValue = '') {
			if (!select) return;
			select.innerHTML = '';
			const placeholder = document.createElement('option');
			placeholder.value = '';
			placeholder.textContent = 'Любой логин (авто)';
			select.appendChild(placeholder);

			(Array.isArray(accounts) ? accounts : []).forEach((account) => {
				if (!account || !account.id) return;
				const option = document.createElement('option');
				option.value = String(account.id);
				const platforms = Array.isArray(account.platforms) && account.platforms.length
					? ` · ${account.platforms.join(', ')}`
					: '';
				const sourceLabel = account.source_label === 'allkeyshop' ? ' · ALLKEYSHOP' : '';
				option.textContent = `${account.login || ('#' + account.id)}${platforms}${sourceLabel}`;
				if (String(account.id) === String(selectedValue)) option.selected = true;
				select.appendChild(option);
			});
		}

		async function refreshDeliveryOptions(order, platformSelect, gameSelect, accountSelect) {
			const issuePlatform = platformSelect?.value || order.issue_platform || order.platform || '';
			const game = gameSelect?.value || '';
			const resp = await apiGet(`/webapp/api/delivery-orders/${order.id}/options?issue_platform=${encodeURIComponent(issuePlatform)}&game=${encodeURIComponent(game)}`);
			if (resp.status !== 200 || !resp.data?.ok) return;

			fillSelect(platformSelect, resp.data.issue_platform_options || [], issuePlatform);
			fillSelect(gameSelect, resp.data.available_games || [], game);
			fillAccountSelect(accountSelect, resp.data.available_accounts || [], '');
		}

		// On game change we only reload the account list, without rebuilding the
		// platform/game selects (rebuilding them resets the native control on mobile).
		async function refreshDeliveryAccounts(order, platformSelect, gameSelect, accountSelect) {
			const issuePlatform = platformSelect?.value || order.issue_platform || order.platform || '';
			const game = gameSelect?.value || '';
			const resp = await apiGet(`/webapp/api/delivery-orders/${order.id}/options?issue_platform=${encodeURIComponent(issuePlatform)}&game=${encodeURIComponent(game)}`);
			if (resp.status !== 200 || !resp.data?.ok) return;

			fillAccountSelect(accountSelect, resp.data.available_accounts || [], '');
		}

		async function deliveryAction(button, url, payload) {
			if (!isBootstrapped) {
				flashDeliveryStatus('Не инициализировано. Обновите Mini App.');
				return;
			}
			if (!isActive) {
				flashDeliveryStatus('Аккаунт на модерации.');
				return;
			}

			setButtonLoading(button, true);
			const resp = await apiPostJson(url, payload || {});
			setButtonLoading(button, false);

			const message = resp.data?.message || resp.data?.error || (resp.status === 200 ? 'Готово' : 'Ошибка');
			flashDeliveryStatus(message);

			if (resp.status === 200 && resp.data?.ok) {
				loadDeliveryOrders();
			}
		}

		function buildDeliveryAssignControls(order) {
			const wrap = document.createElement('div');
			wrap.style.cssText = 'width:100%;margin-top:10px;';

			const platformSelect = document.createElement('select');
			platformSelect.className = 'form-select form-select-sm mb-2';
			fillSelect(platformSelect, order.issue_platform_options || [], order.issue_platform || order.platform || '');

			const gameSelect = document.createElement('select');
			gameSelect.className = 'form-select form-select-sm mb-2';
			fillSelect(gameSelect, order.available_games || [], order.game || '');

			const accountSelect = document.createElement('select');
			accountSelect.className = 'form-select form-select-sm mb-2';
			fillAccountSelect(accountSelect, order.available_accounts || [], '');

			platformSelect.addEventListener('change', () => {
				order.issue_platform = platformSelect.value;
				order.game = '';
				refreshDeliveryOptions(order, platformSelect, gameSelect, accountSelect);
			});

			gameSelect.addEventListener('change', () => {
				order.game = gameSelect.value;
				refreshDeliveryAccounts(order, platformSelect, gameSelect, accountSelect);
			});

			const assignBtn = document.createElement('button');
			assignBtn.type = 'button';
			assignBtn.className = 'btn btn-primary btn-sm w-100';
			assignBtn.textContent = order.account_id ? 'Выдать другой аккаунт' : 'Выдать аккаунт';
			assignBtn.addEventListener('click', () => {
				const game = gameSelect.value;
				const issuePlatform = platformSelect.value;
				if (!game || !issuePlatform) {
					flashDeliveryStatus('Выберите игру и платформу из списка.');
					return;
				}
				const payload = {
					game,
					issue_platform: issuePlatform,
				};
				if (accountSelect.value) {
					payload.account_id = accountSelect.value;
				}
				deliveryAction(assignBtn, `/webapp/api/delivery-orders/${order.id}/assign`, payload);
			});

			wrap.appendChild(platformSelect);
			wrap.appendChild(wrapSearchable(gameSelect));
			wrap.appendChild(accountSelect);
			wrap.appendChild(assignBtn);

			return wrap;
		}

		function buildDeliveryConnectionControls(order, itemId = null) {
			const wrap = document.createElement('div');
			wrap.className = 'd-flex flex-wrap gap-2';
			wrap.style.cssText = 'width:100%;';
			const wi = (p = {}) => itemId ? { ...p, item_id: itemId } : p;

			const connectingBtn = document.createElement('button');
			connectingBtn.type = 'button';
			connectingBtn.className = 'btn btn-outline-secondary btn-sm';
			connectingBtn.textContent = 'Подключаю';
			connectingBtn.addEventListener('click', () => deliveryAction(connectingBtn, `/webapp/api/delivery-orders/${order.id}/connecting`, wi()));

			const connectedBtn = document.createElement('button');
			connectedBtn.type = 'button';
			connectedBtn.className = 'btn btn-success btn-sm';
			connectedBtn.textContent = 'Подключено';
			connectedBtn.addEventListener('click', () => deliveryAction(connectedBtn, `/webapp/api/delivery-orders/${order.id}/connected`, wi()));

			const extraInput = document.createElement('input');
			extraInput.type = 'number';
			extraInput.min = '1';
			extraInput.max = '20';
			extraInput.value = '1';
			extraInput.className = 'form-control form-control-sm';
			extraInput.style.cssText = 'width:80px;';

			const extraBtn = document.createElement('button');
			extraBtn.type = 'button';
			extraBtn.className = 'btn btn-outline-secondary btn-sm';
			extraBtn.textContent = '+ попытки';
			extraBtn.addEventListener('click', () => deliveryAction(extraBtn, `/webapp/api/delivery-orders/${order.id}/extra-attempts`, wi({
				amount: extraInput.value,
			})));

			const failInput = document.createElement('input');
			failInput.type = 'text';
			failInput.className = 'form-control form-control-sm';
			failInput.placeholder = 'Причина ошибки';
			failInput.style.cssText = 'min-width:160px;flex:1 1 160px;';

			const failBtn = document.createElement('button');
			failBtn.type = 'button';
			failBtn.className = 'btn btn-outline-danger btn-sm';
			failBtn.textContent = 'Ошибка';
			failBtn.addEventListener('click', () => deliveryAction(failBtn, `/webapp/api/delivery-orders/${order.id}/failed`, wi({
				reason: failInput.value.trim(),
			})));

			wrap.appendChild(connectingBtn);
			wrap.appendChild(connectedBtn);
			wrap.appendChild(extraInput);
			wrap.appendChild(extraBtn);
			wrap.appendChild(failInput);
			wrap.appendChild(failBtn);

			return wrap;
		}

		function buildDeliveryReplaceControls(order, itemId = null) {
			const wrap = document.createElement('div');
			wrap.style.cssText = 'width:100%;margin-top:8px;';

			const select = document.createElement('select');
			select.className = 'form-select form-select-sm mb-2';

			const reasons = {
				wrong_password: 'Неверный пароль',
				no_access: 'Не входит',
				kicked: 'Клиента выбило',
				dead: 'Аккаунт умер',
				other: 'Другое',
			};

			Object.entries(reasons).forEach(([value, label]) => {
				const option = document.createElement('option');
				option.value = value;
				option.textContent = label;
				select.appendChild(option);
			});

			const btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'btn btn-outline-secondary btn-sm w-100';
			btn.textContent = 'Выдать замену';
			btn.addEventListener('click', () => deliveryAction(btn, `/webapp/api/delivery-orders/${order.id}/replace`, itemId
				? { reason: select.value, item_id: itemId }
				: { reason: select.value }));

			wrap.appendChild(select);
			wrap.appendChild(btn);

			return wrap;
		}

		// "Add game" controls (issues an ADDITIONAL game into the order as a new item).
		function buildAddGameControls(order) {
			const wrap = document.createElement('div');
			wrap.style.cssText = 'width:100%;margin-top:12px;padding-top:12px;border-top:1px dashed rgba(255,255,255,.15);';

			const title = document.createElement('div');
			title.className = 'fw-semibold mb-2';
			title.style.fontSize = '13px';
			title.textContent = '➕ Добавить игру в заказ';
			wrap.appendChild(title);

			const platformSelect = document.createElement('select');
			platformSelect.className = 'form-select form-select-sm mb-2';
			fillSelect(platformSelect, order.issue_platform_options || [], '');

			const gameSelect = document.createElement('select');
			gameSelect.className = 'form-select form-select-sm mb-2';
			fillSelect(gameSelect, [], '');

			const accountSelect = document.createElement('select');
			accountSelect.className = 'form-select form-select-sm mb-2';
			fillAccountSelect(accountSelect, [], '');

			const ctx = { id: order.id, issue_platform: '', game: '' };
			platformSelect.addEventListener('change', () => {
				ctx.issue_platform = platformSelect.value;
				ctx.game = '';
				refreshDeliveryOptions(ctx, platformSelect, gameSelect, accountSelect);
			});
			gameSelect.addEventListener('change', () => {
				ctx.game = gameSelect.value;
				refreshDeliveryAccounts(ctx, platformSelect, gameSelect, accountSelect);
			});

			const addBtn = document.createElement('button');
			addBtn.type = 'button';
			addBtn.className = 'btn btn-outline-primary btn-sm w-100';
			addBtn.textContent = 'Добавить игру';
			addBtn.addEventListener('click', () => {
				const game = gameSelect.value;
				const issuePlatform = platformSelect.value;
				if (!game || !issuePlatform) {
					flashDeliveryStatus('Выберите игру и платформу из списка.');
					return;
				}
				const payload = { game, issue_platform: issuePlatform };
				if (accountSelect.value) payload.account_id = accountSelect.value;
				deliveryAction(addBtn, `/webapp/api/delivery-orders/${order.id}/add-game`, payload);
			});

			wrap.appendChild(platformSelect);
			wrap.appendChild(wrapSearchable(gameSelect));
			wrap.appendChild(accountSelect);
			wrap.appendChild(addBtn);
			return wrap;
		}

		// Renders one additional game (item) with its info + per-item controls.
		function buildItemSection(order, item) {
			const sec = document.createElement('div');
			sec.style.cssText = 'width:100%;margin-top:12px;padding:12px;border:1px solid rgba(255,255,255,.12);border-radius:10px;background:rgba(255,255,255,.03);';

			sec.innerHTML = `
				<div class="list-row"><div class="list-label">🎮 Игра</div><div class="list-value">${escapeHtml(item.game || '-')} <span class="chip">${escapeHtml(item.status_label || item.status || '-')}</span></div></div>
				<div class="list-row"><div class="list-label">Платформа</div><div class="list-value">${escapeHtml(item.issue_platform || item.platform || '-')}</div></div>
				<div class="list-row"><div class="list-label">Логин</div><div class="list-value"><code>${escapeHtml(item.display_login || '-')}</code></div></div>
				<div class="list-row"><div class="list-label">Пароль</div><div class="list-value"><code>${escapeHtml(item.display_password || '-')}</code>${item.display_password_type === 'fake' ? ' <span class="chip">fake</span>' : ''}</div></div>
				<div class="list-row"><div class="list-label">Код</div><div class="list-value"><code>${escapeHtml(item.last_connection_code || '-')}</code></div></div>
				<div class="list-row"><div class="list-label">Попытки</div><div class="list-value">${escapeHtml(item.connection_attempts_used || 0)} / ${escapeHtml(item.connection_attempts_limit || 0)}</div></div>
			`;

			if (item.status === 'connected') {
				const done = document.createElement('div');
				done.style.cssText = 'margin-top:8px;padding:8px 10px;border-radius:8px;background:rgba(40,167,69,.15);border:1px solid rgba(40,167,69,.4);color:#a8d8a8;font-size:12px;';
				done.textContent = '✅ Подключено. Поля зафиксированы.';
				sec.appendChild(done);
			} else {
				sec.appendChild(buildDeliveryConnectionControls(order, item.id));
				sec.appendChild(buildDeliveryReplaceControls(order, item.id));
			}
			return sec;
		}

		function renderDeliveryOrders(items) {
			deliveryList.innerHTML = '';

			if (!items || items.length === 0) {
				const empty = document.createElement('div');
				empty.className = 'list-empty';
				empty.textContent = 'Нет данных';
				deliveryList.appendChild(empty);
				return;
			}

			items.forEach((order) => {
				const card = document.createElement('div');
				card.className = 'list-card order-card';
				if (focusedDeliveryOrderId && String(order.id) === String(focusedDeliveryOrderId)) {
					card.style.border = '1px solid rgba(36,129,201,.7)';
				}

				const replacements = (order.replacements || []).map((event) => {
					const payload = event.payload || {};
					return `<div class="list-row"><div class="list-label">Замена</div><div class="list-value">#${escapeHtml(payload.old_account_id || '-')} → #${escapeHtml(payload.new_account_id || '-')} · ${escapeHtml(payload.reason || '-')}</div></div>`;
				}).join('');

				card.innerHTML = `
					<div class="list-row">
						<div class="list-label">Заказ</div>
						<div class="list-value">${escapeHtml(order.order_number || '-')}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Статус</div>
						<div class="list-value"><span class="chip">${escapeHtml(order.status_label || order.status || '-')}</span></div>
					</div>
					<div class="list-row">
						<div class="list-label">Email</div>
						<div class="list-value">${escapeHtml(order.customer_email || '-')}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Запрос клиента</div>
						<div class="list-value">${escapeHtml(order.platform || '-')}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Выдано</div>
						<div class="list-value">${escapeHtml(order.issue_platform || '-')}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Игра</div>
						<div class="list-value">${escapeHtml(order.game || '-')}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Аккаунт</div>
						<div class="list-value">${order.account_id ? '#' + escapeHtml(order.account_id) : '-'}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Логин</div>
						<div class="list-value"><code>${escapeHtml(order.display_login || '-')}</code></div>
					</div>
					<div class="list-row">
						<div class="list-label">Пароль</div>
						<div class="list-value"><code>${escapeHtml(order.display_password || '-')}</code>${order.display_password_type === 'fake' ? ' <span class="chip">fake</span>' : ''}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Код</div>
						<div class="list-value"><code>${escapeHtml(order.last_connection_code || '-')}</code></div>
					</div>
					<div class="list-row">
						<div class="list-label">Попытки</div>
						<div class="list-value">${escapeHtml(order.connection_attempts_used || 0)} / ${escapeHtml(order.connection_attempts_limit || 0)}</div>
					</div>
					<div class="list-row">
						<div class="list-label">Оператор</div>
						<div class="list-value">${escapeHtml(order.operator_name || order.operator_telegram_id || '-')}</div>
					</div>
					${replacements}
				`;

				const actions = document.createElement('div');
				actions.className = 'list-actions';

				// First game (lives on the order)
				if (order.status === 'connected') {
					const done = document.createElement('div');
					done.style.cssText = 'width:100%;padding:10px 12px;border-radius:10px;background:rgba(40,167,69,.15);border:1px solid rgba(40,167,69,.4);color:#a8d8a8;font-size:13px;line-height:1.5;';
					done.textContent = '✅ Первая игра подключена. Поля зафиксированы.';
					actions.appendChild(done);
				} else if (!['cancelled', 'expired'].includes(order.status)) {
					actions.appendChild(buildDeliveryAssignControls(order));
					if (order.account_id) {
						actions.appendChild(buildDeliveryConnectionControls(order));
						actions.appendChild(buildDeliveryReplaceControls(order));
					}
				}

				// Additional games (items)
				(order.items || []).forEach((item) => actions.appendChild(buildItemSection(order, item)));

				// Add another game
				if (!['cancelled', 'expired'].includes(order.status)) {
					actions.appendChild(buildAddGameControls(order));
				}

				// Cancel the whole order (refund/chargeback)
				if (!['cancelled', 'expired'].includes(order.status)) {
					const cancelWrap = document.createElement('div');
					cancelWrap.style.cssText = 'width:100%;margin-top:12px;';
					const cancelBtn = document.createElement('button');
					cancelBtn.type = 'button';
					cancelBtn.className = 'btn btn-outline-danger btn-sm w-100';
					cancelBtn.textContent = 'Отменить заказ';
					cancelBtn.addEventListener('click', () => {
						if (confirm('Отменить заказ? Клиент потеряет доступ к данным на странице.')) {
							deliveryAction(cancelBtn, `/webapp/api/delivery-orders/${order.id}/cancel`, {});
						}
					});
					cancelWrap.appendChild(cancelBtn);
					actions.appendChild(cancelWrap);
				}

				card.appendChild(actions);
				deliveryList.appendChild(card);
			});
		}

		async function loadDeliveryOrders() {
			if (!isActive) {
				flashDeliveryStatus('Аккаунт на модерации.');
				return;
			}

			flashDeliveryStatus('Загрузка...');
			const params = new URLSearchParams();
			params.set('limit', '50');
			const q = deliverySearchInput?.value.trim() || '';
			if (q) params.set('q', q);
			if (focusedDeliveryOrderId) params.set('delivery_order', focusedDeliveryOrderId);

			const resp = await apiGet(`/webapp/api/delivery-orders?${params.toString()}`);

			if (resp.status === 403) {
				flashDeliveryStatus(resp.data?.error || 'Нет доступа.');
				return;
			}

			if (resp.status !== 200 || !resp.data?.ok) {
				flashDeliveryStatus(resp.data?.error || 'Ошибка загрузки.');
				return;
			}

			flashDeliveryStatus('');
			renderDeliveryOrders(resp.data.items || []);
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

		const replacementReasonLabels = {
			wrong_password: 'Неверный пароль',
			kicked: 'Клиента выбило',
			kick: 'Выбрасывало из игры',
			wrong_platform: 'Не та платформа',
			no_access: 'Нет доступа к аккаунту',
			dead: 'Аккаунт сломан',
			other: 'Другое',
		};

		function buildReplaceButton(item) {
			const wrap = document.createElement('div');
			wrap.style.cssText = 'width:100%;';

			const btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'btn btn-outline-secondary btn-sm';
			btn.textContent = 'Замена';
			wrap.appendChild(btn);

			const panel = document.createElement('div');
			panel.style.cssText = 'display:none;margin-top:8px;';

			const select = document.createElement('select');
			select.className = 'form-select form-select-sm mb-2';
			Object.entries(replacementReasonLabels).forEach(([val, label]) => {
				const opt = document.createElement('option');
				opt.value = val;
				opt.textContent = label;
				select.appendChild(opt);
			});

			const otherInput = document.createElement('input');
			otherInput.type = 'text';
			otherInput.className = 'form-control form-control-sm mb-2';
			otherInput.placeholder = 'Опишите причину...';
			otherInput.style.display = 'none';

			select.addEventListener('change', () => {
				otherInput.style.display = select.value === 'other' ? 'block' : 'none';
			});

			const resultBox = document.createElement('div');
			resultBox.style.cssText = 'display:none;margin-top:8px;';
			resultBox.className = 'codebox';

			const confirmBtn = document.createElement('button');
			confirmBtn.type = 'button';
			confirmBtn.className = 'btn btn-primary btn-sm w-100';
			confirmBtn.textContent = 'Подтвердить замену';

			confirmBtn.addEventListener('click', async () => {
				const reason = select.value === 'other'
					? (otherInput.value.trim() || 'other')
					: select.value;

				setButtonLoading(confirmBtn, true);
				const resp = await apiPostJson('/webapp/api/replace', {
					issuance_id: item.issuance_id,
					reason,
				});
				setButtonLoading(confirmBtn, false);

				if (resp.status === 200 && resp.data?.ok) {
					resultBox.style.display = 'block';
					resultBox.style.cssText = 'display:block;margin-top:8px;padding:10px;border-radius:8px;background:rgba(0,0,0,.45);border:1px solid rgba(255,255,255,.1);font-size:13px;white-space:pre-wrap;word-break:break-word;';
					resultBox.textContent = `Замена выполнена.\nАккаунт: #${resp.data.account_id}\nЛогин: ${resp.data.login}\nПароль: ${resp.data.password}`;
					confirmBtn.disabled = true;
					select.disabled = true;
					otherInput.disabled = true;
					loadHistory();
				} else {
					flashHistoryStatus(resp.data?.error || 'Ошибка замены.');
				}
			});

			panel.appendChild(select);
			panel.appendChild(otherInput);
			panel.appendChild(confirmBtn);
			panel.appendChild(resultBox);
			wrap.appendChild(panel);

			btn.addEventListener('click', () => {
				panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
			});

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

				let statusBadge = '';
				if (item.is_replaced) {
					const reasonLabel = item.replacement_reason ? (replacementReasonLabels[item.replacement_reason] || item.replacement_reason) : '';
					statusBadge = `<div class="list-row"><div class="list-label">Статус</div><div class="list-value"><span style="display:inline-block;padding:2px 8px;border-radius:999px;background:rgba(220,53,69,.18);border:1px solid rgba(220,53,69,.4);color:#e05c5c;font-size:11px;font-weight:600;">Заменён${reasonLabel ? ' · ' + reasonLabel : ''}</span></div></div>`;
				} else if (item.is_replacement) {
					const reasonLabel = item.replacement_reason ? (replacementReasonLabels[item.replacement_reason] || item.replacement_reason) : '';
					statusBadge = `<div class="list-row"><div class="list-label">Статус</div><div class="list-value"><span style="display:inline-block;padding:2px 8px;border-radius:999px;background:rgba(36,129,201,.18);border:1px solid rgba(36,129,201,.4);color:#2481C9;font-size:11px;font-weight:600;">Замена${reasonLabel ? ' · ' + reasonLabel : ''}</span></div></div>`;
				}

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
					${statusBadge}
				`;

				const actions = document.createElement('div');
				actions.className = 'list-actions';
				actions.appendChild(buildProblemButtons(item));
				if (!item.is_replaced && !item.is_replacement) {
					actions.appendChild(buildReplaceButton(item));
				}
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

		refreshDeliveryBtn?.addEventListener('click', () => {
			loadDeliveryOrders();
		});

		deliverySearchInput?.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') {
				loadDeliveryOrders();
			}
		});

		function initSearchableSelect(selectId, showSearch = true) {
			attachSearchableSelect(document.getElementById(selectId), showSearch);
		}

		// Same widget for dynamically created selects (delivery order cards).
		function attachSearchableSelect(select, showSearch = true) {
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

				// Keep the trigger label in sync with the select value: dynamic
				// cards start with a value selected, and a platform change
				// resets the game select back to the placeholder.
				const triggerText = trigger.querySelector('.trigger-text');
				if (triggerText) {
					const current = options.find((o) => o.value === currentValue && o.value !== '');
					if (current) {
						triggerText.textContent = current.text;
						triggerText.classList.remove('trigger-placeholder');
					} else {
						triggerText.textContent = select.options[0]?.text || 'Выберите...';
						triggerText.classList.add('trigger-placeholder');
					}
				}

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
						<div class="list-label">Аккаунт</div>
						<div class="list-value">${item.account_id ? '#' + item.account_id : '-'}</div>
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
				const initialTab = initialParams.get('tab');
				if (isDeliveryOperator || initialTab === 'delivery' || focusedDeliveryOrderId) {
					switchTab('delivery');
					loadDeliveryOrders();
				} else {
					// Загружаем stolen сразу для показа бейджа
					loadStolen();
				}
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

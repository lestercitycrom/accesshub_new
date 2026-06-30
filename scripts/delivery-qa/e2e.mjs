import { chromium } from 'playwright';

const base = process.env.QA_BASE || 'https://download-games.info';
const results = [];
const ok = (name, cond, extra = '') => { results.push({ name, pass: !!cond, extra }); };
const skip = (name, extra = '') => { results.push({ name, skip: true, extra }); };

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
const page = await ctx.newPage();

try {
	// ---------- Take-order page (read-only) ----------
	const resp = await page.goto(base + '/take-order', { waitUntil: 'domcontentloaded' });
	ok('take-order HTTP 200', resp && resp.status() === 200, String(resp && resp.status()));

	ok('hero "delivered"', (await page.locator('h1', { hasText: 'delivered' }).count()) > 0);
	ok('card "Track your order"', (await page.locator('h2', { hasText: 'Track your order' }).count()) > 0);

	const ph = await page.locator('#order_number').getAttribute('placeholder');
	ok('order# placeholder', /order number/i.test(ph || ''), ph);

	const seller = page.locator('a', { hasText: 'Contact us' });
	const sellerHref = await seller.getAttribute('href');
	ok('Contact us -> difmark', sellerHref === 'https://difmark.com/en/profile/GlobalGames', sellerHref);

	// Working-hours widget
	ok('hours widget visible', await page.locator('#hoursWidget').isVisible());
	const range = (await page.locator('#hoursRange').textContent())?.trim();
	ok('support hours range shown', /\d{2}:00.+\d{2}:00 EET/.test(range || ''), range);
	const clock = (await page.locator('#hoursTime').textContent())?.trim();
	ok('live clock format HH:MM:SS EET', /^\d{2}:\d{2}:\d{2} EET$/.test(clock || ''), clock);
	const status = (await page.locator('#hoursStatus').textContent())?.trim();
	ok('status Online/Offline', /Online|Offline/.test(status || ''), status);

	// State-aware: night block
	const state = await page.evaluate(() => ({
		enforce: !!(window.__deliveryHours && window.__deliveryHours.enforce),
		open: typeof window.deliveryHoursIsOpen === 'function' ? window.deliveryHoursIsOpen() : true,
	}));
	const closed = state.enforce && !state.open;
	const noticeVisible = await page.locator('#offlineNotice').isVisible();
	const submitDisabled = await page.locator('#submitBtn').isDisabled();
	ok(`night-block consistent (closed=${closed})`,
		closed ? (noticeVisible && submitDisabled) : (!noticeVisible && !submitDisabled),
		`notice=${noticeVisible} submitDisabled=${submitDisabled}`);

	// ---------- Server-side check via direct POST (uses page session + CSRF).
	// Neither branch creates an order: closed => blocked before validation;
	// open => empty payload fails validation. ----------
	const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
	if (closed) {
		const r = await page.request.post(base + '/take-order', {
			form: { _token: csrf, order_number: 'E2E-NIGHT-PROBE', email: 'e2e@example.com', platform: 'Xbox' },
		});
		const body = await r.text();
		ok('closed: server blocks order (night)', /support hours/i.test(body), `status=${r.status()}`);
	} else {
		const r = await page.request.post(base + '/take-order', { form: { _token: csrf } });
		const body = await r.text();
		ok('open: English validation (required, not RU)', /required/i.test(body) && !/обязательно/.test(body), `status=${r.status()}`);
	}

	// ---------- Tutorial pages on branded domain ----------
	for (const console of ['epic', 'steam']) {
		const r = await page.goto(`${base}/tutorial.php?lang=en&console=${console}`, { waitUntil: 'domcontentloaded' });
		const bodyLen = (await page.locator('body').textContent())?.length || 0;
		ok(`tutorial ${console} loads (200, has content)`, r && r.status() === 200 && bodyLen > 400, `status=${r && r.status()} len=${bodyLen}`);
	}

	// ---------- Order page: tutorial button + direct delivery (temp Steam/Epic) ----------
	for (const [token, consoleParam, login] of [
		['qa-pwtest-steam', 'steam', 'steam-login@example.com'],
		['qa-pwtest-epic', 'epic', 'epic-login@example.com'],
	]) {
		const r = await page.goto(`${base}/order/${token}`, { waitUntil: 'domcontentloaded' });
		if (!r || r.status() !== 200) {
			// Fixture not seeded — these order-page checks are optional.
			skip(`order ${token} (not seeded; run delivery:qa:seed)`, String(r && r.status()));
			continue;
		}
		await page.waitForTimeout(800);
		const acct = (await page.locator('#accountBlock').textContent()) || '';
		ok(`${token}: login shown`, acct.includes(login), '');
		const tut = page.locator('#instructionBlock a', { hasText: 'installation tutorial' });
		const tutHref = await tut.getAttribute('href').catch(() => null);
		ok(`${token}: tutorial button -> console=${consoleParam}`, !!tutHref && tutHref.includes(`console=${consoleParam}`), tutHref || 'missing');
		const connHidden = await page.locator('#connectionCard').evaluate((el) => el.classList.contains('hidden')).catch(() => null);
		ok(`${token}: no QR/connection card (direct delivery)`, connHidden === true, `hidden=${connHidden}`);
	}
} finally {
	await browser.close();
}

let failed = 0;
let skipped = 0;
for (const r of results) {
	const tag = r.skip ? 'SKIP' : (r.pass ? 'PASS' : 'FAIL');
	if (r.skip) skipped++;
	else if (!r.pass) failed++;
	console.log(`[${tag}] ${r.name}${r.extra ? '  (' + r.extra + ')' : ''}`);
}
const total = results.length - skipped;
console.log(`\n${total - failed}/${total} passed${skipped ? `, ${skipped} skipped` : ''}${failed ? `, ${failed} FAILED` : ''}`);
process.exit(failed ? 1 : 0);

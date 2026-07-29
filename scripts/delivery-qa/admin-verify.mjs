import { chromium } from 'playwright';

const base = process.env.QA_BASE || 'https://download-games.info';
const email = process.env.QA_ADMIN_EMAIL || 'admin@gmail.com';
const pass = process.env.QA_ADMIN_PASS || 'admin123';
const dir = 'scripts/delivery-qa';
const log = (...a) => console.log(...a);

async function login(page) {
	await page.goto(base + '/login', { waitUntil: 'networkidle' });
	await page.fill('input[name="email"]', email);
	await page.fill('input[name="password"]', pass);
	await page.waitForTimeout(300);
	await page.click('button[type="submit"]');
	await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 15000 }).catch(() => {});
	await page.waitForLoadState('networkidle').catch(() => {});
	await page.waitForTimeout(800);
	return !page.url().endsWith('/login');
}

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1366, height: 900 } });
const page = await ctx.newPage();

const ok = await login(page);
log('login ok:', ok, '| url:', page.url());
if (!ok) {
	await page.screenshot({ path: `${dir}/verify_login_fail.png` });
	const err = await page.locator('.text-red-600, [class*="red"]').allInnerTexts().catch(() => []);
	log('login error text:', err.join(' | '));
	await browser.close();
	process.exit(1);
}

const shot = async (n) => { await page.screenshot({ path: `${dir}/verify_${n}.png` }); log('shot', n); };

// Accounts list
await page.goto(base + '/admin/accounts', { waitUntil: 'networkidle' });
await page.waitForTimeout(800);
const search = page.locator('input[placeholder*="игра"], input[placeholder*="логин"]').first();
log('search placeholder:', await search.getAttribute('placeholder').catch(() => null));
await shot('accounts_list');

// Header "Аккаунты" dropdown (hover = desktop behaviour)
const acctNav = page.locator('header button:has-text("Аккаунты")').first();
if (await acctNav.count()) { await acctNav.hover(); await page.waitForTimeout(500); await shot('menu_accounts_open'); }

// User dropdown (rightmost header button)
const userBtn = page.locator('header button').last();
await userBtn.hover().catch(() => {});
await page.waitForTimeout(500);
await shot('menu_user_open');

// Search
await page.goto(base + '/admin/accounts', { waitUntil: 'networkidle' });
const s2 = page.locator('input[placeholder*="игра"], input[placeholder*="логин"]').first();
await s2.fill('a');
await page.waitForTimeout(1500);
await shot('accounts_search');

// Account detail (the "Открыть" eye link in a row)
const view = page.locator('a[title="Открыть"]').first();
const href = await view.getAttribute('href').catch(() => null);
if (href) {
	await page.goto(href.startsWith('http') ? href : base + href, { waitUntil: 'networkidle' });
	await page.waitForTimeout(600);
	await shot('account_detail');
	const txt = await page.locator('body').innerText();
	log('detail 2FA:', /2FA/i.test(txt), '| Recover:', /Код восстановления/.test(txt));
}

// Links + orders
await page.goto(base + '/admin/delivery-links', { waitUntil: 'networkidle' }); await page.waitForTimeout(500); await shot('delivery_links');
await page.goto(base + '/admin/delivery-orders', { waitUntil: 'networkidle' }); await page.waitForTimeout(500); await shot('delivery_orders');

// Mobile
const m = await browser.newContext({ viewport: { width: 390, height: 820 } });
const mp = await m.newPage();
if (await login(mp)) {
	await mp.goto(base + '/admin/accounts', { waitUntil: 'networkidle' });
	await mp.waitForTimeout(800);
	await mp.screenshot({ path: `${dir}/verify_mobile_accounts.png` });
	log('shot mobile_accounts');
}

await browser.close();
await m.close();
log('DONE');

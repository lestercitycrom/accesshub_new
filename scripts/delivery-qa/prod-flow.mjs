import { chromium } from 'playwright';

const base = process.env.QA_BASE || 'https://download-games.info';
const results = [];
const ok = (name, cond, extra = '') => { results.push({ name, pass: !!cond }); console.log((cond ? 'PASS' : 'FAIL') + ' — ' + name + (extra ? '  [' + extra + ']' : '')); };

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 }, permissions: ['clipboard-read', 'clipboard-write'] });
const page = await ctx.newPage();

// track failed asset responses + connection-code POSTs
const failedAssets = [];
let connPosts = 0;
page.on('response', (r) => { if (r.url().includes('/site/mock/') && r.status() !== 200) failedAssets.push(r.url() + ' ' + r.status()); });
page.on('request', (r) => { if (r.url().includes('connection-code') && r.method() === 'POST') connPosts++; });

// ---------- 1) take-order ----------
await page.goto(base + '/take-order', { waitUntil: 'networkidle' });
ok('take-order loads', true);
ok('all mock assets 200', failedAssets.length === 0, failedAssets.join(', '));
ok('favicon links present', (await page.locator('link[href*="favicon-gg"]').count()) >= 2);
ok('hero image visible', await page.locator('img[src*="mock/hero"]').isVisible());
ok('headline overlay', (await page.locator('h1').textContent() || '').includes('delivered'));

// clock ticks
const t1 = await page.locator('#hoursTime').textContent();
await page.waitForTimeout(1600);
const t2 = await page.locator('#hoursTime').textContent();
ok('header clock ticking', t1 !== t2, `${t1} -> ${t2}`);

// platform tiles: click each, verify checked
for (const pf of ['PlayStation', 'Xbox', 'Nintendo', 'Steam', 'Epic Games']) {
	await page.locator(`input[value="${pf}"]`).check({ force: true });
	if (!(await page.locator(`input[value="${pf}"]`).isChecked())) { ok('platform selectable: ' + pf, false); }
}
ok('all 5 platform tiles selectable', true);

// inputs accept text
await page.fill('#order_number', 'QA-INPUT-1');
await page.fill('#email', 'qa@example.com');
ok('inputs accept text', (await page.inputValue('#order_number')) === 'QA-INPUT-1' && (await page.inputValue('#email')) === 'qa@example.com');

// night-block consistency (works in both open/closed states)
const state = await page.evaluate(() => ({
	enforce: typeof window.deliveryHoursEnforced === 'function' ? window.deliveryHoursEnforced() : false,
	open: typeof window.deliveryHoursIsOpen === 'function' ? window.deliveryHoursIsOpen() : true,
}));
const closed = state.enforce && !state.open;
const noticeVisible = await page.locator('#offlineNotice').isVisible();
const submitDisabled = await page.locator('#submitBtn').isDisabled();
ok(`night-block UI consistent (closed=${closed})`, closed ? (noticeVisible && submitDisabled) : (!noticeVisible && !submitDisabled), `notice=${noticeVisible} disabled=${submitDisabled}`);
if (closed) {
	const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
	const r = await page.request.post(base + '/take-order', { form: { _token: csrf, order_number: 'QA-NIGHT-PROBE', email: 'qa@example.com', platform: 'Xbox' } });
	ok('closed: server blocks order POST', /support hours/i.test(await r.text()), 'status=' + r.status());
} else {
	console.log('INFO — store is OPEN: skipping real submit (would notify operators)');
}

// footer + nav hrefs
ok('Contact us -> difmark', (await page.locator('header a:has-text("Contact us")').getAttribute('href') || '').includes('difmark.com'));
ok('How it works link', (await page.locator('header a:has-text("How it works")').getAttribute('href') || '').includes('how-it-works'));
ok('Browse our store -> difmark', (await page.locator('a:has-text("Browse our store")').getAttribute('href') || '').includes('difmark.com'));

await page.screenshot({ path: 'scripts/delivery-qa/prod_takeorder.png', fullPage: true });

// ---------- 2) unique links ----------
await page.goto(base + '/take-order/qaprod0000link01', { waitUntil: 'networkidle' });
ok('unused coded link opens form', /take-order\/qaprod0000link01/.test(await page.locator('#takeOrderForm').getAttribute('action') || ''));
await page.goto(base + '/take-order/qaprod0000link02', { waitUntil: 'networkidle' });
ok('used coded link redirects to its order', page.url().endsWith('/order/qa-prod-multi'), page.url());
const r404 = await page.request.get(base + '/take-order/qaprodnope000000');
ok('unknown code -> 404', r404.status() === 404, String(r404.status()));

// ---------- 3) order page: waiting (Processing) ----------
let statusFetches = 0;
page.on('request', (r) => { if (r.url().includes('/status')) statusFetches++; });
await page.goto(base + '/order/qa-prod-wait', { waitUntil: 'networkidle' });
await page.waitForTimeout(1000);
ok('processing banner', /Processing/.test(await page.locator('#bannerBig').textContent() || ''));
ok('stepper rendered (4 circles)', (await page.locator('#stepper img').count()) === 4);
ok("what's happening visible", await page.locator('#happeningCard').isVisible());
ok('what to do next visible', await page.locator('#todoCard').isVisible());
ok('account card hidden', !(await page.locator('#accountCard').isVisible()));
ok('gamepad art shown', (await page.locator('#instrArt').getAttribute('src') || '').includes('gamepad'));
await page.waitForTimeout(9000);
ok('status polling fires', statusFetches >= 1, String(statusFetches));
await page.screenshot({ path: 'scripts/delivery-qa/prod_wait.png', fullPage: true });

// ---------- 4) order page: connection code (client-side only, no POST) ----------
await page.goto(base + '/order/qa-prod-conn', { waitUntil: 'networkidle' });
await page.waitForTimeout(1000);
ok('connection card visible', await page.locator('#connectionCard').isVisible());
ok('attempts text', /0 of 3/.test(await page.locator('#attemptsText').textContent() || ''));
await page.fill('#connection_code', 'AB');
await page.click('#connSubmit');
await page.waitForTimeout(600);
const nativeBlocked = await page.locator('#connection_code').evaluate((i) => !i.validity.valid);
ok('client-side code validation blocks short code', nativeBlocked || await page.locator('#connError').isVisible());
ok('no POST sent for invalid code', connPosts === 0, String(connPosts));
await page.screenshot({ path: 'scripts/delivery-qa/prod_conn.png', fullPage: true });

// ---------- 5) order page: delivered multi-game ----------
await page.goto(base + '/order/qa-prod-multi', { waitUntil: 'networkidle' });
await page.waitForTimeout(1000);
ok('delivered banner', /Delivered/.test(await page.locator('#bannerBig').textContent() || ''));
ok('multi-game: 2 tabs', (await page.locator('#tabBar button').count()) === 2);
ok('account details visible', await page.locator('#accountCard').isVisible());
const before = await page.locator('#accountBlock').textContent() || '';
ok('password masked', before.includes('•') && !before.includes('QaPass!77x'));
await page.locator('#accountBlock button[title="Show"]').click();
await page.waitForTimeout(300);
ok('eye reveals password', ((await page.locator('#accountBlock').textContent()) || '').includes('QaPass!77x'));
await page.locator('#accountBlock .copy-btn').first().click();
await page.waitForTimeout(300);
const clip = await page.evaluate(() => navigator.clipboard.readText().catch(() => ''));
ok('copy login to clipboard', clip === 'qaepic@example.com', clip);
// switch tab
await page.locator('#tabBar button').nth(1).click();
await page.waitForTimeout(400);
ok('tab switch -> game changes', ((await page.locator('#dGame').textContent()) || '').includes('QA FC 25'));
ok('tab switch -> creds change', ((await page.locator('#accountBlock').textContent()) || '').includes('qasteam@example.com'));
ok('guide art shown', (await page.locator('#instrArt').getAttribute('src') || '').includes('guide'));
ok('order # copy btn present', (await page.locator('.copy-btn[data-copy="QA-PW-3"]').count()) === 1);
ok('Contact support -> difmark', (await page.locator('#whatsNextCard a:has-text("Contact support")').getAttribute('href') || '').includes('difmark.com'));
await page.screenshot({ path: 'scripts/delivery-qa/prod_multi.png', fullPage: true });

// ---------- 6) how-it-works + transitions ----------
await page.goto(base + '/how-it-works', { waitUntil: 'networkidle' });
const det = page.locator('details').first();
await det.locator('summary').click();
ok('accordion expands', await det.evaluate((d) => d.open));
await page.click('a:has-text("Track your order")');
await page.waitForURL(/take-order$/, { timeout: 10000 }).catch(() => {});
ok('hiw -> take-order button navigates', /take-order$/.test(page.url()), page.url());
// logo click navigates home (take-order)
await page.goto(base + '/how-it-works', { waitUntil: 'domcontentloaded' });
await page.click('header a[href*="take-order"]');
await page.waitForURL(/take-order/, { timeout: 10000 }).catch(() => {});
ok('logo -> take-order', /take-order/.test(page.url()));

// ---------- 7) mobile spot check ----------
const m = await browser.newContext({ viewport: { width: 390, height: 844 } });
const mp = await m.newPage();
await mp.goto(base + '/take-order', { waitUntil: 'networkidle' });
const cardBox = await mp.locator('#takeOrderForm').boundingBox();
ok('mobile: form usable width', cardBox && cardBox.width >= 290, cardBox ? String(Math.round(cardBox.width)) : 'none');
const overflow = await mp.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
ok('mobile: no horizontal overflow', !overflow);
await mp.screenshot({ path: 'scripts/delivery-qa/prod_mobile.png', fullPage: true });
await mp.goto(base + '/order/qa-prod-multi', { waitUntil: 'networkidle' });
await mp.waitForTimeout(800);
const mOverflow = await mp.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
ok('mobile order: no horizontal overflow', !mOverflow);
await m.close();

await browser.close();
const failed = results.filter((r) => !r.pass);
console.log('\n==== ' + (results.length - failed.length) + '/' + results.length + ' passed ====');
process.exit(failed.length ? 1 : 0);

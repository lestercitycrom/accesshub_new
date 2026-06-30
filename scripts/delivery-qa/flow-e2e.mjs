import { chromium } from 'playwright';

const base = process.env.QA_BASE || 'http://127.0.0.1:8123';
const results = [];
const ok = (name, cond, extra = '') => { results.push({ name, pass: !!cond, extra }); console.log((cond ? 'PASS' : 'FAIL') + ' — ' + name + (extra ? '  [' + extra + ']' : '')); };

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1280, height: 1000 }, permissions: ['clipboard-read', 'clipboard-write'] });
const page = await ctx.newPage();

// ---------- 1) take-order: valid submit creates order ----------
await page.goto(base + '/take-order', { waitUntil: 'networkidle' });
ok('take-order loads', page.url().endsWith('/take-order'));
await page.check('input[value="PlayStation"]', { force: true });
ok('platform selectable', await page.locator('input[value="PlayStation"]').isChecked());
await page.fill('#order_number', 'E2E-FORM-' + Date.now());
await page.fill('#email', 'e2e@example.com');
await Promise.all([page.waitForURL(/\/order\//, { timeout: 15000 }).catch(() => {}), page.click('#submitBtn')]);
ok('submit redirects to order page', /\/order\//.test(page.url()), page.url());
await page.waitForTimeout(800);
const big1 = (await page.locator('#bannerBig').textContent().catch(() => '')) || '';
ok('new order shows Processing', /Processing/i.test(big1), big1);
const steps = await page.locator('#stepper > div').count();
ok('stepper has 4 steps', steps === 4, String(steps));

// ---------- 2) unique code link: opens form, consumes, re-open redirects ----------
await page.goto(base + '/take-order/qae2elink0000aaa', { waitUntil: 'networkidle' });
const action = await page.locator('#takeOrderForm').getAttribute('action');
ok('coded link form posts to coded route', /take-order\/qae2elink0000aaa/.test(action || ''), action);
await page.check('input[value="Steam"]', { force: true });
await page.fill('#order_number', 'E2E-CODE-' + Date.now());
await page.fill('#email', 'e2ecode@example.com');
await Promise.all([page.waitForURL(/\/order\//, { timeout: 15000 }).catch(() => {}), page.click('#submitBtn')]);
const codeOrderUrl = page.url();
ok('coded submit creates order', /\/order\//.test(codeOrderUrl), codeOrderUrl);
await page.goto(base + '/take-order/qae2elink0000aaa', { waitUntil: 'networkidle' });
ok('used code re-open redirects to its order', page.url() === codeOrderUrl, page.url());

// ---------- 3) delivered + multi-game tabs + password reveal + copy ----------
await page.goto(base + '/order/qa-e2e-multi', { waitUntil: 'networkidle' });
await page.waitForTimeout(900);
ok('delivered banner', /Delivered/i.test((await page.locator('#bannerBig').textContent()) || ''));
const tabs = await page.locator('#tabBar button').count();
ok('multi-game: 2 tabs shown', tabs === 2, String(tabs));
ok('account card visible', await page.locator('#accountCard').isVisible());
const pwBefore = (await page.locator('#accountBlock').textContent()) || '';
ok('password masked by default', pwBefore.includes('•') && !pwBefore.includes('Aw2!pass'));
await page.locator('#accountBlock button[title="Show"]').click();
await page.waitForTimeout(200);
const pwAfter = (await page.locator('#accountBlock').textContent()) || '';
ok('eye reveals real password', pwAfter.includes('Aw2!pass'), '');
// copy
await page.locator('#accountBlock .copy-btn').first().click();
await page.waitForTimeout(150);
const clip = await page.evaluate(() => navigator.clipboard.readText().catch(() => ''));
ok('copy puts login in clipboard', clip.length > 0, clip);
// switch tab
await page.locator('#tabBar button').nth(1).click();
await page.waitForTimeout(300);
ok('tab switch changes game', ((await page.locator('#dGame').textContent()) || '').includes('EA FC 25'), await page.locator('#dGame').textContent());
ok('tab switch changes account', ((await page.locator('#accountBlock').textContent()) || '').includes('fc25player'));

// ---------- 4) connection code submit ----------
await page.goto(base + '/order/qa-e2e-conn', { waitUntil: 'networkidle' });
await page.waitForTimeout(900);
ok('connection card visible', await page.locator('#connectionCard').isVisible());
ok('connection form visible', await page.locator('#connForm').isVisible());
ok('attempts shown', /0 of 3/.test((await page.locator('#attemptsText').textContent()) || ''));
await page.fill('#connection_code', 'ABC123');
await page.click('#connSubmit');
await page.waitForResponse((r) => r.url().includes('connection-code'), { timeout: 10000 }).catch(() => {});
await page.waitForTimeout(700);
const progressVisible = await page.locator('#connProgress').isVisible().catch(() => false);
const errText = (await page.locator('#connErrorText').textContent().catch(() => '')) || '';
ok('code submit accepted (progress shown)', progressVisible, 'err=' + errText);

// ---------- 5) how-it-works accordions ----------
await page.goto(base + '/how-it-works', { waitUntil: 'networkidle' });
ok('how-it-works loads', /how-it-works/.test(page.url()));
const det = page.locator('details').first();
await det.locator('summary').click();
await page.waitForTimeout(200);
ok('FAQ/instruction accordion expands', await det.evaluate((d) => d.open));

await browser.close();
const failed = results.filter((r) => !r.pass);
console.log('\n==== ' + (results.length - failed.length) + '/' + results.length + ' passed ====');
process.exit(failed.length ? 1 : 0);

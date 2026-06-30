import { mkdir } from 'node:fs/promises';
import { chromium } from 'playwright';

const base = process.env.QA_BASE || 'http://127.0.0.1:8012';
const out = process.env.QA_OUTPUT || 'flow/tasks/002_chester_admin_ui/screenshots';

const checks = [];
const record = (name, pass, detail = '') => {
	checks.push({ name, pass: Boolean(pass), detail });
	console.log(`[${pass ? 'PASS' : 'FAIL'}] ${name}${detail ? ` (${detail})` : ''}`);
};

const assertNoHorizontalOverflow = async (page, name) => {
	const metrics = await page.evaluate(() => {
		const root = document.documentElement;
		const body = document.body;
		const candidates = Array.from(document.querySelectorAll('body, main, [data-admin-shell], table'));
		const overflows = candidates
			.map((el) => ({
				tag: el.tagName,
				classes: el.className ? String(el.className).slice(0, 100) : '',
				scrollWidth: el.scrollWidth,
				clientWidth: el.clientWidth,
			}))
			.filter((x) => x.scrollWidth > x.clientWidth + 1);

		return {
			rootScrollWidth: root.scrollWidth,
			rootClientWidth: root.clientWidth,
			bodyScrollWidth: body.scrollWidth,
			bodyClientWidth: body.clientWidth,
			overflows,
		};
	});

	record(
		name,
		metrics.rootScrollWidth <= metrics.rootClientWidth + 1 && metrics.bodyScrollWidth <= metrics.bodyClientWidth + 1,
		JSON.stringify(metrics),
	);
};

await mkdir(out, { recursive: true });

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1280, height: 720 }, deviceScaleFactor: 1 });
const page = await context.newPage();

try {
	await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' });
	await page.locator('input[name="email"]').waitFor({ state: 'visible' });
	await page.locator('input[name="email"]').fill('codex-admin@example.com');
	await page.locator('input[name="password"]').fill('password');
	await page.locator('button[type="submit"]').click();
	await page.waitForURL(/admin|dashboard|take-order|delivery-orders/, { timeout: 15000 }).catch(() => {});

	await page.goto(`${base}/admin/delivery-orders`, { waitUntil: 'domcontentloaded' });
	await page.locator('table').waitFor({ state: 'visible' });
	await page.screenshot({ path: `${out}/delivery-orders-1280.png`, fullPage: true });

	await assertNoHorizontalOverflow(page, 'Delivery Orders has no page horizontal overflow at 1280px');

	const orderRows = await page.locator('tbody tr').count();
	record('Delivery Orders renders compact 17-row page', orderRows === 17, `rows=${orderRows}`);
	record('Delivery Orders shows seeded game', await page.getByText('Alan Wake 2').first().isVisible());
	record('Delivery Orders shows account column', await page.getByText('account1@example.com').first().isVisible());
	record('Delivery Orders shows open action', await page.getByRole('link', { name: 'Открыть' }).first().isVisible());

	await page.setViewportSize({ width: 1365, height: 768 });
	await page.goto(`${base}/admin/delivery-orders`, { waitUntil: 'domcontentloaded' });
	await page.locator('table').waitFor({ state: 'visible' });
	await page.screenshot({ path: `${out}/delivery-orders-1365.png`, fullPage: true });
	await assertNoHorizontalOverflow(page, 'Delivery Orders has no page horizontal overflow at 1365px');

	await page.goto(`${base}/admin/delivery-links`, { waitUntil: 'domcontentloaded' });
	await page.locator('input[type="number"]').waitFor({ state: 'visible' });
	await page.locator('input[type="number"]').fill('3');
	await page.locator('input[list="delivery-link-game-options"]').fill('Playwright QA Game');
	await page.locator('input[placeholder="e.g. offer EA FC 25 PS5"]').fill('Playwright batch');
	await page.getByRole('button', { name: 'Generate' }).click();
	await page.getByText(/Generated 3 links/).waitFor({ state: 'visible', timeout: 15000 });

	await page.screenshot({ path: `${out}/delivery-links-batches.png`, fullPage: true });
	await assertNoHorizontalOverflow(page, 'Delivery Links has no page horizontal overflow at 1365px');
	record('Delivery Links batch table shows generated game', await page.getByText('Playwright QA Game').first().isVisible());
	record('Delivery Links generation success message visible', await page.getByText(/Generated 3 links/).first().isVisible());
} finally {
	await browser.close();
}

const failed = checks.filter((x) => !x.pass);
console.log(`\n${checks.length - failed.length}/${checks.length} checks passed`);
if (failed.length > 0) {
	process.exit(1);
}

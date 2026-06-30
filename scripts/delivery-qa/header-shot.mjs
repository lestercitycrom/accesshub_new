import { chromium } from 'playwright';

const base = process.env.QA_BASE || 'http://127.0.0.1:8123';
const browser = await chromium.launch();

const widths = [320, 360, 390, 768, 1280];
for (const w of widths) {
	const ctx = await browser.newContext({ viewport: { width: w, height: 720 }, deviceScaleFactor: 2 });
	const page = await ctx.newPage();
	await page.goto(base + '/take-order', { waitUntil: 'networkidle' });
	// header-only screenshot
	const header = page.locator('header.appbar');
	await header.screenshot({ path: `scripts/delivery-qa/header_${w}.png` });
	const overflow = await page.evaluate(() => {
		const el = document.querySelector('header.appbar .appbar-inner');
		const hours = document.querySelector('.hours');
		return {
			docOverflow: document.documentElement.scrollWidth > window.innerWidth,
			hoursRight: hours ? Math.round(hours.getBoundingClientRect().right) : null,
			innerWidth: window.innerWidth,
		};
	});
	console.log(`w=${w}`, JSON.stringify(overflow));
	await ctx.close();
}

await browser.close();

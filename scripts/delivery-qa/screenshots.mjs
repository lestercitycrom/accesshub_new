import { mkdir } from 'node:fs/promises';
import { chromium } from 'playwright';

const baseUrl = process.env.QA_BASE || 'http://127.0.0.1:8012';
const outputDir = process.env.QA_OUTPUT || 'flow/tasks/001_chester_delivery_frontend/screenshots';

const viewports = {
	mobile_360: { width: 360, height: 800 },
	tablet_768: { width: 768, height: 1024 },
	desktop_1280: { width: 1280, height: 900 },
};

const pages = [
	['take', '/take-order', ['mobile_360', 'tablet_768', 'desktop_1280']],
	['order_waiting', '/order/qa-waiting-0001', ['mobile_360']],
	['order_qr', '/order/qa-qr-0001', ['mobile_360', 'tablet_768', 'desktop_1280']],
	['order_locked', '/order/qa-locked-0001', ['mobile_360', 'desktop_1280']],
];

await mkdir(outputDir, { recursive: true });

const browser = await chromium.launch();

try {
	for (const [prefix, path, viewportKeys] of pages) {
		for (const viewportKey of viewportKeys) {
			const context = await browser.newContext({
				viewport: viewports[viewportKey],
				deviceScaleFactor: 2,
			});

			const page = await context.newPage();
			await page.goto(baseUrl + path, { waitUntil: 'networkidle' });
			await page.waitForTimeout(1200);

			const file = `${outputDir}/${prefix}_${viewportKey}.png`;
			await page.screenshot({ path: file, fullPage: true });
			console.log(`saved ${file}`);

			await context.close();
		}
	}
} finally {
	await browser.close();
}

console.log('Delivery QA screenshots complete.');

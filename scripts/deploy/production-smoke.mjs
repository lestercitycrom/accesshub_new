import { createHmac } from 'node:crypto';
import { mkdir } from 'node:fs/promises';
import { chromium } from 'playwright';

const requiredEnv = [
    'QA_BASE',
    'QA_MARKER',
    'QA_OPERATOR_EMAIL',
    'QA_OPERATOR_PASSWORD',
    'QA_OPERATOR_TOTP_SECRET',
    'QA_MANAGER_EMAIL',
    'QA_MANAGER_PASSWORD',
    'QA_MANAGER_TOTP_SECRET',
];

for (const name of requiredEnv) {
    if (!process.env[name]) {
        throw new Error(`Missing required environment variable: ${name}`);
    }
}

const base = process.env.QA_BASE.replace(/\/$/, '');
const marker = process.env.QA_MARKER;
const artifactDir = process.env.QA_ARTIFACT_DIR || '';

if (!/^https:\/\//i.test(base) && process.env.QA_ALLOW_HTTP !== '1') {
    throw new Error('QA_BASE must use HTTPS (set QA_ALLOW_HTTP=1 only for a local environment).');
}

if (!/^CODEX-E2E-[A-Z0-9-]+$/.test(marker)) {
    throw new Error('QA_MARKER must match CODEX-E2E-[A-Z0-9-]+ so cleanup can be safely scoped.');
}

const accountLogin = `${marker.toLowerCase()}@example.invalid`;
const accountPassword = `Smoke-${marker}-Password`;
const mailLogin = accountLogin;
const gameBase = `Smoke ${marker}`;
const results = [];

function record(name, pass, detail = '') {
    results.push({ name, pass, detail });
    console.log(`[${pass ? 'PASS' : 'FAIL'}] ${name}${detail ? ` (${detail})` : ''}`);
    if (!pass) {
        throw new Error(`${name}${detail ? `: ${detail}` : ''}`);
    }
}

function decodeBase32(value) {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    let bits = '';

    for (const character of value.toUpperCase().replace(/=|\s/g, '')) {
        const index = alphabet.indexOf(character);
        if (index < 0) throw new Error('Invalid base32 TOTP secret.');
        bits += index.toString(2).padStart(5, '0');
    }

    const bytes = [];
    for (let offset = 0; offset + 8 <= bits.length; offset += 8) {
        bytes.push(Number.parseInt(bits.slice(offset, offset + 8), 2));
    }

    return Buffer.from(bytes);
}

function totp(secret, timestamp = Date.now()) {
    const counter = BigInt(Math.floor(timestamp / 30_000));
    const message = Buffer.alloc(8);
    message.writeBigUInt64BE(counter);

    const digest = createHmac('sha1', decodeBase32(secret)).update(message).digest();
    const offset = digest[digest.length - 1] & 0x0f;
    const binary = (digest.readUInt32BE(offset) & 0x7fffffff) % 1_000_000;

    return String(binary).padStart(6, '0');
}

async function capture(page, name) {
    if (!artifactDir) return;
    await mkdir(artifactDir, { recursive: true });
    await page.screenshot({ path: `${artifactDir}/${name}.png`, fullPage: true });
}

async function login(page, credentials, label) {
    const response = await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' });
    record(`${label}: login page returns HTTP 200`, response?.status() === 200, String(response?.status()));

    await page.locator('input[name="email"]').fill(credentials.email);
    await page.locator('input[name="password"]').fill(credentials.password);
    await Promise.all([
        page.waitForURL(/\/two-factor-challenge(?:\?|$)/, { timeout: 15_000 }),
        page.locator('button[type="submit"]').click(),
    ]);
    record(`${label}: password step requires 2FA`, /\/two-factor-challenge(?:\?|$)/.test(page.url()));

    const otpInput = page.locator('[data-test="two-factor-code"]');
    record(`${label}: OTP input is visible`, await otpInput.isVisible());
    await otpInput.fill(totp(credentials.totpSecret));

    await Promise.all([
        page.waitForURL(/\/admin\/accounts(?:\?|$)/, { timeout: 15_000 }),
        page.getByRole('button', { name: 'Продолжить' }).click(),
    ]);
    record(`${label}: 2FA login opens accounts page`, /\/admin\/accounts(?:\?|$)/.test(page.url()), page.url());
}

async function createAccount(page, { game, platform, cooldownDays }) {
    await page.goto(`${base}/admin/accounts/create`, { waitUntil: 'networkidle' });
    record(`${game}: create form opens`, await page.getByRole('heading', { name: 'Создание аккаунта' }).isVisible());

    await page.locator('input[name="game"]').fill(game);
    await page.locator('select[wire\\:model="platformSelected"]').selectOption(platform);
    await page.locator('input[name="maxUses"]').fill('1');
    await page.locator('input[name="availableUses"]').fill('1');
    await page.locator('input[name="cooldownDays"]').fill(String(cooldownDays));
    await page.locator('input[name="login"]').fill(accountLogin);
    await page.locator('input[name="password"]').fill(accountPassword);
    await page.locator('[wire\\:model="mailAccountLogin"]').fill(mailLogin);
    await page.locator('[wire\\:model="mailAccountPassword"]').fill(accountPassword);

    await Promise.all([
        page.waitForURL(/\/admin\/accounts(?:\?|$)/, { timeout: 15_000 }),
        page.getByRole('button', { name: 'Сохранить' }).click(),
    ]);
    record(`${game}: account is created through Livewire UI`, /\/admin\/accounts(?:\?|$)/.test(page.url()));
}

const browser = await chromium.launch({ headless: true });
let activePage;

try {
    const operatorContext = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const operatorPage = await operatorContext.newPage();
    activePage = operatorPage;

    await login(operatorPage, {
        email: process.env.QA_OPERATOR_EMAIL,
        password: process.env.QA_OPERATOR_PASSWORD,
        totpSecret: process.env.QA_OPERATOR_TOTP_SECRET,
    }, 'operator');

    record('operator: create action is visible', await operatorPage.getByRole('link', { name: 'Создать' }).isVisible());
    record('operator: account search is hidden', await operatorPage.getByRole('link', { name: 'Поиск' }).count() === 0);
    record('operator: CSV export is hidden', await operatorPage.getByRole('link', { name: 'Экспорт CSV' }).count() === 0);

    await operatorPage.goto(`${base}/admin/accounts/create`, { waitUntil: 'networkidle' });
    record('operator: cooldown override is visible', await operatorPage.locator('input[name="cooldownDays"]').isVisible());
    record('operator: status controls are hidden', await operatorPage.locator('[wire\\:model="status"]').count() === 0);
    record('operator: problem flags are hidden', await operatorPage.locator('[wire\\:model="flagActionRequired"]').count() === 0);
    await createAccount(operatorPage, { game: `${gameBase} A`, platform: 'Steam', cooldownDays: 30 });
    await capture(operatorPage, '01-operator-created');
    await operatorContext.close();

    const managerContext = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const managerPage = await managerContext.newPage();
    activePage = managerPage;

    await login(managerPage, {
        email: process.env.QA_MANAGER_EMAIL,
        password: process.env.QA_MANAGER_PASSWORD,
        totpSecret: process.env.QA_MANAGER_TOTP_SECRET,
    }, 'manager');

    await createAccount(managerPage, { game: `${gameBase} B`, platform: 'Steam', cooldownDays: 30 });
    await createAccount(managerPage, { game: `${gameBase} Epic`, platform: 'Epic Games', cooldownDays: 30 });

    await managerPage.goto(`${base}/admin/accounts`, { waitUntil: 'networkidle' });
    const duplicateButton = managerPage.getByRole('button', { name: 'Показать дубли' });
    record('manager: duplicate filter is visible', await duplicateButton.isVisible());
    await duplicateButton.click();
    await managerPage.getByRole('button', { name: 'Дубли показаны' }).waitFor({ timeout: 15_000 });

    const markerRows = managerPage.locator('tbody tr').filter({ hasText: accountLogin });
    const markerRowCount = await markerRows.count();
    record('duplicate filter: same-platform pair is returned', markerRowCount === 2, `rows=${markerRowCount}`);

    const markerRowsText = await markerRows.allTextContents();
    record('duplicate filter: different-platform account is excluded', !markerRowsText.some((text) => text.includes(`${gameBase} Epic`)));
    await capture(managerPage, '02-duplicates-filter');

    const firstDetailsLink = markerRows.first().locator('a[title="Открыть"]');
    record('manager: matching account can be opened', await firstDetailsLink.isVisible());
    await Promise.all([
        managerPage.waitForURL(/\/admin\/accounts\/\d+(?:\?|$)/, { timeout: 15_000 }),
        firstDetailsLink.click(),
    ]);
    record('account details: custom cooldown is displayed', await managerPage.getByText('30 дн.', { exact: true }).isVisible());
    await capture(managerPage, '03-account-cooldown');
    await managerContext.close();
} catch (error) {
    if (activePage) await capture(activePage, 'failure').catch(() => {});
    console.error(`\nPlaywright smoke failed: ${error.stack || error.message}`);
    process.exitCode = 1;
} finally {
    await browser.close();
}

const failed = results.filter((result) => !result.pass).length;
console.log(`\n${results.length - failed}/${results.length} Playwright checks passed.`);
if (failed > 0) process.exitCode = 1;

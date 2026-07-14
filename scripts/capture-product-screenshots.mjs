import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const output = resolve(root, 'public/images/product');
const env = readEnvironment(resolve(root, '.env'));
const baseUrl = (
    process.env.HOOKROUTE_SCREENSHOT_BASE_URL ||
    env.APP_URL ||
    'http://localhost'
).replace(/\/$/, '');

if (!existsSync(resolve(root, 'vendor/bin/sail'))) {
    fail('Laravel Sail is not installed. Run composer install first.');
}

if (process.env.HOOKROUTE_SCREENSHOT_SKIP_SEED !== '1') {
    console.log('Refreshing the isolated demo dataset...');
    try {
        execFileSync(
            resolve(root, 'vendor/bin/sail'),
            ['artisan', 'db:seed', '--class=DemoSeeder', '--force'],
            { cwd: root, stdio: 'inherit' },
        );
    } catch {
        fail(
            'Could not seed through Sail. Start the stack with sail up -d and try again.',
        );
    }
}

mkdirSync(output, { recursive: true });

let browser;

try {
    browser = await chromium.launch();
} catch (error) {
    if (String(error).includes("Executable doesn't exist")) {
        fail(
            'Chromium is missing. Run npm run screenshots:install once, then retry.',
        );
    }

    throw error;
}

try {
    const context = await browser.newContext({
        viewport: { width: 1440, height: 1100 },
        deviceScaleFactor: 1,
        colorScheme: 'light',
        locale: 'en-GB',
        reducedMotion: 'reduce',
        timezoneId: 'Europe/Berlin',
    });
    const page = await context.newPage();

    await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
    await page.getByLabel('Email').fill('demo@hookroute.test');
    await page.getByLabel('Password').fill('password');
    await Promise.all([
        page.waitForURL(/\/projects\/production-systems$/),
        page.getByRole('button', { name: 'Log in' }).click(),
    ]);

    await capture(
        page,
        `${baseUrl}/projects/production-systems`,
        'dashboard.jpg',
    );

    await page.goto(`${baseUrl}/projects/production-systems/routes`, {
        waitUntil: 'domcontentloaded',
    });
    await page.getByRole('row', { name: /GitHub → Discord/ }).click();
    await page.locator('.dialog-backdrop').waitFor({ state: 'visible' });
    await page.getByRole('heading', { name: 'Edit route' }).waitFor({
        state: 'visible',
    });
    await settle(page);
    await page.screenshot({
        path: resolve(output, 'route-editor.jpg'),
        type: 'jpeg',
        quality: 88,
        animations: 'disabled',
    });
    console.log('Captured public/images/product/route-editor.jpg');

    await page.goto(`${baseUrl}/projects/production-systems/events`, {
        waitUntil: 'domcontentloaded',
    });
    await page.locator('tbody tr').first().click();
    await page.getByRole('heading', { name: 'Parsed payload' }).waitFor();
    await settle(page);
    await page.screenshot({
        path: resolve(output, 'event-detail.jpg'),
        type: 'jpeg',
        quality: 88,
        animations: 'disabled',
    });
    console.log('Captured public/images/product/event-detail.jpg');

    await context.close();
} finally {
    await browser.close();
}

function readEnvironment(path) {
    if (!existsSync(path)) return {};

    return Object.fromEntries(
        readFileSync(path, 'utf8')
            .split(/\r?\n/)
            .filter(
                (line) => line && !line.startsWith('#') && line.includes('='),
            )
            .map((line) => {
                const index = line.indexOf('=');
                return [
                    line.slice(0, index),
                    line.slice(index + 1).replace(/^['"]|['"]$/g, ''),
                ];
            }),
    );
}

async function capture(page, url, filename) {
    await page.goto(url, { waitUntil: 'domcontentloaded' });
    await page.locator('main').waitFor();
    await settle(page);
    await page.screenshot({
        path: resolve(output, filename),
        type: 'jpeg',
        quality: 88,
        animations: 'disabled',
    });
    console.log(`Captured public/images/product/${filename}`);
}

async function settle(page) {
    await page.evaluate(async () => {
        await document.fonts.ready;
        document.querySelectorAll('[style*="animation"]').forEach((element) => {
            element.style.animation = 'none';
        });
    });
}

function fail(message) {
    console.error(`\n${message}\n`);
    process.exit(1);
}

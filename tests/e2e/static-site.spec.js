import { test, expect } from '@playwright/test';
import { shot } from './helpers.js';

/*
 * Kiểm bản demo tĩnh (GitHub Pages) sau khi `php artisan site:export`.
 * Chạy: STATIC_BASE_URL=http://127.0.0.1:8090/trai-ech-hoa-binh/ npx playwright test static-site
 */
const BASE = process.env.STATIC_BASE_URL;

test.describe('bản demo tĩnh', () => {
    test.skip(!BASE, 'Chỉ chạy khi có STATIC_BASE_URL');

    test('bò toàn site: mọi link nội bộ 200, không thiếu tài nguyên, không lỗi JS', async ({ page }, testInfo) => {
        test.setTimeout(180_000);
        const problems = [];
        page.on('pageerror', (e) => problems.push(`JS ${page.url()}: ${e.message}`));
        page.on('console', (m) => m.type() === 'error' && problems.push(`console ${page.url()}: ${m.text()}`));
        page.on('response', (r) => r.url().startsWith(BASE) && r.status() >= 400 && problems.push(`${r.status()} ${r.url()} (từ ${page.url()})`));
        page.on('request', (r) => {
            // Bản tĩnh không được gửi gì về server (Livewire update, POST form...).
            if (r.method() !== 'GET') problems.push(`${r.method()} ${r.url()}`);
        });

        const queue = [BASE];
        const seen = new Set();
        while (queue.length) {
            const url = queue.shift();
            if (seen.has(url)) continue;
            seen.add(url);
            const res = await page.goto(url, { waitUntil: 'networkidle' });
            expect(res?.status(), url).toBe(200);
            const links = await page.$$eval('a[href]', (as) => as.map((a) => a.href.split('#')[0]));
            for (const link of links) {
                if (link.startsWith(BASE) && !seen.has(link) && !/\.(xml|svg|png|jpe?g)$/.test(link)) queue.push(link);
            }
        }

        console.log(`Đã duyệt ${seen.size} trang`);
        expect(seen.size).toBeGreaterThan(30);
        expect(problems, problems.join('\n')).toEqual([]);
    });

    test('ảnh chụp và tương tác Alpine trên bản tĩnh', async ({ page }, testInfo) => {
        await page.goto(BASE);
        await expect(page.getByText(/^Bản (xem thử \(demo tĩnh\)|demo) —/).filter({ visible: true })).toHaveCount(1);
        await shot(page, testInfo, 's01-home');

        if (testInfo.project.name === 'mobile') {
            await page.getByRole('button', { name: 'Mở menu' }).click();
            await expect(page.getByRole('dialog', { name: 'Menu danh mục' })).toBeVisible();
            await page.waitForTimeout(400);
            await shot(page, testInfo, 's02-menu');
            await page.keyboard.press('Escape');
        } else {
            await page.locator('nav[aria-label="Danh mục chính"]').getByRole('link', { name: 'Ếch giống', exact: true }).hover();
            await expect(page.getByRole('link', { name: 'Nòng nọc' }).first()).toBeVisible();
            await shot(page, testInfo, 's02-dropdown');
        }

        await page.goto(BASE + 'san-pham/ech-giong-thai-lan-size-120-150-conkg/');
        await expect(page.getByText('Bảng giá theo quy cách')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Thêm vào giỏ' })).toHaveCount(0);
        await page.getByRole('tab', { name: /Đánh giá/ }).click();
        await shot(page, testInfo, 's03-product');

        const missing = await page.goto(BASE + 'khong-ton-tai/');
        expect(missing?.status()).toBe(404);
    });
});

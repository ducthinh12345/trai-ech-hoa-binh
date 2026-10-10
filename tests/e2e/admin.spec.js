import { test, expect } from '@playwright/test';
import { ADMIN_STATE } from './helpers.js';
import { shot, watchErrors } from './helpers.js';

test.use({ storageState: ADMIN_STATE });
test.skip(!!process.env.STATIC_BASE_URL, 'Đang kiểm bản tĩnh');

test.describe('admin', () => {
    test.beforeEach(({}, testInfo) => {
        test.skip(testInfo.project.name === 'mobile' && !process.env.E2E_ADMIN_MOBILE, 'Admin kiểm trên desktop');
    });

    test('dashboard, menu người dùng, thông báo', async ({ page }, testInfo) => {
        const errs = watchErrors(page);
        await page.goto('/admin');
        await page.waitForLoadState('networkidle');
        await shot(page, testInfo, 'a01-dashboard');
        await page.getByRole('button', { name: 'Menu người dùng' }).click();
        await page.waitForTimeout(300);
        await shot(page, testInfo, 'a02-user-menu');
        await page.keyboard.press('Escape');
        await page.getByRole('button', { name: 'Mở thông báo' }).click();
        await page.waitForTimeout(600);
        await shot(page, testInfo, 'a02b-notifications');
        await page.keyboard.press('Escape');
        errs.assertClean();
    });

    test('đơn hàng: bộ lọc, nhóm hành động', async ({ page }, testInfo) => {
        const errs = watchErrors(page);
        await page.goto('/admin/orders?tab=all');
        await page.waitForLoadState('networkidle');
        await shot(page, testInfo, 'a03-orders');
        await page.locator('.fi-ta-filters-trigger-action-ctn button, button[title*="Lọc"], button:has-text("Bộ lọc")').first().click();
        await page.waitForTimeout(400);
        await shot(page, testInfo, 'a04-orders-filter');
        await page.keyboard.press('Escape');

        await page.locator('.fi-ta-row').first().click();
        await page.waitForURL(/admin\/orders\/TE-/);
        await page.waitForLoadState('networkidle');
        await shot(page, testInfo, 'a05-order-view');
        await page.getByRole('button', { name: 'Thêm' }).click();
        await page.waitForTimeout(300);
        await shot(page, testInfo, 'a06-order-more');
        errs.assertClean();
    });

    test('sản phẩm: form với Select danh mục, quy cách, ngày đăng', async ({ page }, testInfo) => {
        const errs = watchErrors(page);
        await page.goto('/admin/products/create');
        await page.waitForLoadState('networkidle');
        await shot(page, testInfo, 'a07-product-create');
        await page.getByLabel('Danh mục').first().click();
        await page.waitForTimeout(400);
        await shot(page, testInfo, 'a08-select-category');
        await page.keyboard.press('Escape');
        await page.getByLabel('Hẹn giờ đăng').first().click();
        await page.waitForTimeout(400);
        await shot(page, testInfo, 'a09-datepicker');
        errs.assertClean();
    });

    test('cài đặt, phí vận chuyển (Select nhiều tỉnh)', async ({ page }, testInfo) => {
        const errs = watchErrors(page);
        await page.goto('/admin/settings');
        await page.waitForLoadState('networkidle');
        await shot(page, testInfo, 'a10-settings');
        await page.goto('/admin/shipping-methods');
        await page.getByRole('button', { name: 'Thêm phương thức' }).click();
        await page.waitForTimeout(500);
        await page.getByLabel('Áp dụng cho tỉnh/thành').click();
        await page.waitForTimeout(400);
        await shot(page, testInfo, 'a11-multiselect');
        errs.assertClean();
    });
});

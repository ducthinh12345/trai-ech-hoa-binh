import { test, expect } from '@playwright/test';
import { shot, watchErrors } from './helpers.js';

test.skip(!!process.env.STATIC_BASE_URL, 'Đang kiểm bản tĩnh');

test('header: dropdown danh mục (desktop) / menu trượt (mobile)', async ({ page }, testInfo) => {
    const errs = watchErrors(page);
    await page.goto('/');
    await shot(page, testInfo, '01-home');

    if (testInfo.project.name === 'desktop') {
        const trigger = page.locator('nav[aria-label="Danh mục chính"]').getByRole('link', { name: 'Ếch giống', exact: true });
        await trigger.hover();
        const submenu = page.locator('nav[aria-label="Danh mục chính"]').getByRole('link', { name: 'Ếch giống Thái Lan' });
        await expect(submenu).toBeVisible();
        await page.waitForTimeout(400);
        await shot(page, testInfo, '02-header-dropdown');
        // Rê chuột từ menu cha xuống menu con không được làm dropdown tắt giữa chừng.
        const box = await submenu.boundingBox();
        await page.mouse.move(box.x + 10, box.y + box.height / 2, { steps: 8 });
        await expect(submenu).toBeVisible();
        await submenu.click();
        await expect(page).toHaveURL(/danh-muc\/ech-giong-thai-lan/);
    } else {
        await page.getByRole('button', { name: 'Mở menu' }).click();
        const drawer = page.getByRole('dialog');
        await expect(drawer).toBeVisible();
        await page.waitForTimeout(400);
        await shot(page, testInfo, '02-mobile-menu');
        await drawer.getByRole('button', { name: 'Mở danh mục con' }).first().click();
        await expect(drawer.getByRole('link', { name: 'Ếch giống Thái Lan' })).toBeVisible();
        await page.waitForTimeout(400);
        await shot(page, testInfo, '03-mobile-submenu');
        await drawer.getByRole('button', { name: 'Đóng menu' }).click();
        await expect(drawer).toBeHidden();

        await page.getByRole('button', { name: 'Tìm kiếm' }).first().click();
        await expect(page.getByPlaceholder('Bạn cần tìm gì?')).toBeVisible();
        await shot(page, testInfo, '04-mobile-search');
    }
    errs.assertClean();
});

test('danh sách: dropdown sắp xếp + bộ lọc', async ({ page }, testInfo) => {
    const errs = watchErrors(page);
    await page.goto('/san-pham');
    const sort = page.locator('select#sort');
    await expect(sort).toBeVisible();
    await shot(page, testInfo, '05-listing');
    await sort.selectOption('price_asc');
    await expect(page).toHaveURL(/sort=price_asc/);
    await shot(page, testInfo, '06-listing-sorted');

    if (testInfo.project.name === 'mobile') {
        await page.getByRole('button', { name: 'Bộ lọc' }).click();
        await page.waitForTimeout(400);
        await shot(page, testInfo, '07-mobile-filters');
        await expect(page.getByRole('heading', { name: 'Khoảng giá' })).toBeInViewport();
        await page.getByRole('button', { name: 'Đóng bộ lọc' }).click();
        await expect(page.getByRole('dialog', { name: 'Bộ lọc' })).toBeHidden();
    }
    errs.assertClean();
});

test('sản phẩm → giỏ → checkout: chọn quy cách, tỉnh, vận chuyển', async ({ page }, testInfo) => {
    const errs = watchErrors(page);
    await page.goto('/san-pham/ech-giong-thai-lan-size-120-150-conkg');
    await page.getByText('Túi 500 con').click();
    await expect(page.getByText('850.000đ').first()).toBeVisible();
    await shot(page, testInfo, '08-product-variant');
    await page.getByRole('button', { name: 'Thêm vào giỏ' }).click();
    await expect(page.getByText(/Đã thêm/)).toBeVisible();
    await shot(page, testInfo, '09-toast');

    await page.goto('/gio-hang');
    await shot(page, testInfo, '10-cart');
    await page.getByRole('link', { name: /Tiến hành đặt hàng/ }).click();
    await expect(page).toHaveURL(/thanh-toan/);

    const province = page.locator('select#provinceCode');
    await shot(page, testInfo, '11-checkout');
    await province.selectOption('HN');
    await expect(page.getByText('Giao nhanh Hà Nội')).toBeVisible();
    await page.waitForTimeout(300);
    await shot(page, testInfo, '12-checkout-province');
    errs.assertClean();
});

test('tài khoản: dropdown tỉnh trong sổ địa chỉ và trang lỗi', async ({ page }, testInfo) => {
    const errs = watchErrors(page);
    await page.goto('/tra-cuu-don-hang');
    await shot(page, testInfo, '13-track');
    await page.goto('/lien-he');
    await expect(page.locator('select#c-subject')).toBeVisible();
    await page.locator('details').first().click();
    await shot(page, testInfo, '14-contact');
    errs.assertClean();
});

test('danh mục: chỉ mở danh mục con ở nhánh đang xem', async ({ page }, testInfo) => {
    const errs = watchErrors(page);
    const openFilters = async () => {
        if (testInfo.project.name === 'mobile') {
            await page.getByRole('button', { name: 'Bộ lọc' }).click();
            return page.getByRole('dialog', { name: 'Bộ lọc' });
        }
        return page.locator('aside');
    };

    // Ở nhánh khác: danh mục con của "Ếch giống" phải ẩn, bấm mũi tên mới hiện.
    await page.goto('/danh-muc/ech-thit-song');
    let panel = await openFilters();
    await expect(panel.getByRole('link', { name: 'Ếch giống Thái Lan' })).toBeHidden();
    await expect(panel.getByRole('link', { name: /^Ếch thịt sống/ })).toHaveAttribute('aria-current', 'page');
    await shot(page, testInfo, '15-category-other-branch');
    await panel.getByRole('button', { name: 'Danh mục con của Ếch giống' }).click();
    await expect(panel.getByRole('link', { name: 'Ếch giống Thái Lan' })).toBeVisible();

    // Ở danh mục con: nhánh cha mở sẵn, mục con được đánh dấu đang xem.
    await page.goto('/danh-muc/ech-giong-thai-lan');
    panel = await openFilters();
    await expect(panel.getByRole('link', { name: 'Ếch giống Thái Lan' })).toBeVisible();
    await expect(panel.getByRole('link', { name: 'Ếch giống Thái Lan' })).toHaveAttribute('aria-current', 'page');
    await shot(page, testInfo, '16-category-child-active');

    // Trang tất cả sản phẩm: không nhánh nào mở sẵn.
    await page.goto('/san-pham');
    panel = await openFilters();
    await expect(panel.getByRole('link', { name: 'Nòng nọc' })).toBeHidden();

    if (testInfo.project.name === 'desktop') {
        await page.goto('/danh-muc/nong-noc');
        await expect(page.locator('nav[aria-label="Danh mục chính"]').getByRole('link', { name: 'Ếch giống', exact: true })).toHaveClass(/(^|\s)text-brand-600(\s|$)/);
        await expect(page.locator('nav[aria-label="Danh mục chính"]').getByRole('link', { name: 'Ếch thịt sống', exact: true })).toHaveClass(/(^|\s)text-brand-900(\s|$)/);
    }
    errs.assertClean();
});

import { test as setup } from '@playwright/test';

import { ADMIN_STATE } from './helpers.js';

/*
 * Đăng nhập admin một lần rồi lưu cookie cho mọi test admin: tránh bấm nút trước khi Livewire khởi tạo
 * (race làm test chập chờn) và tránh giới hạn 5 lần đăng nhập/phút của Filament.
 */
setup('đăng nhập admin', async ({ page }) => {
    await page.goto('/admin/login');
    await page.waitForFunction(() => window.Livewire && document.querySelector('form[wire\\:submit]'));
    await page.getByRole('textbox', { name: /Địa chỉ email/ }).fill(process.env.E2E_ADMIN_EMAIL ?? 'admin@traiechhoabinh.vn');
    await page.getByRole('textbox', { name: /^Mật khẩu/ }).fill(process.env.E2E_ADMIN_PASSWORD ?? 'password');
    await page.getByRole('button', { name: 'Đăng nhập' }).click();
    await page.waitForURL(/\/admin\/?$/, { waitUntil: 'domcontentloaded' });
    await page.context().storageState({ path: ADMIN_STATE });
});

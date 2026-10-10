import { defineConfig, devices } from '@playwright/test';

/*
 * Kiểm thử giao diện bằng trình duyệt thật. Chạy trên server dev đang mở (php artisan serve).
 * Ảnh chụp lưu ở tests/e2e/screenshots để xem lại bằng mắt sau mỗi lần sửa.
 */
export default defineConfig({
    testDir: 'tests/e2e',
    outputDir: 'tests/e2e/.results',
    timeout: 60_000,
    fullyParallel: false,
    workers: 1,
    reporter: [['list']],
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000',
        locale: 'vi-VN',
        trace: 'retain-on-failure',
    },
    /* Dùng server đang chạy nếu có; nếu không thì tự bật với nhiều worker vì dashboard gọi song song nhiều request. */
    webServer: {
        command: 'php artisan serve --port=8000',
        url: 'http://127.0.0.1:8000/up',
        reuseExistingServer: true,
        env: { PHP_CLI_SERVER_WORKERS: '4' },
        timeout: 30_000,
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.js/, use: { ...devices['Desktop Chrome'] } },
        { name: 'desktop', dependencies: ['setup'], use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } } },
        { name: 'mobile', dependencies: ['setup'], use: { ...devices['Pixel 7'] } },
    ],
});

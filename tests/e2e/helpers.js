import { expect } from '@playwright/test';

export const shot = (page, testInfo, name) =>
    page.screenshot({ path: `tests/e2e/screenshots/${testInfo.project.name}-${name}.png`, fullPage: false });

/**
 * Gom lỗi JS và request hỏng trong suốt test; cuối test assert rỗng để lỗi im lặng không lọt qua.
 */
export function watchErrors(page) {
    const errors = [];
    page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
    page.on('console', (m) => m.type() === 'error' && errors.push(`console: ${m.text()}`));
    page.on('response', (r) => r.status() >= 500 && errors.push(`HTTP ${r.status()} ${r.url()}`));
    return { assertClean: () => expect(errors, errors.join('\n')).toEqual([]) };
}


export const ADMIN_STATE = 'tests/e2e/.results/admin-state.json';

import { defineConfig, devices } from '@playwright/test';

const executablePath = process.env['CHROME_BIN'];

export default defineConfig({
    testDir: './e2e',
    timeout: 30_000,
    fullyParallel: true,
    forbidOnly: !!process.env['CI'],
    retries: process.env['CI'] ? 2 : 0,
    reporter: process.env['CI'] ? 'github' : 'list',
    use: {
        baseURL: process.env['E2E_BASE_URL'] ?? 'http://wapinet.loc',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        // Reuse the locally installed Chromium-based browser (Yandex Browser
        // is exposed through CHROME_BIN) instead of downloading a separate one.
        launchOptions: executablePath ? { executablePath } : {},
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});

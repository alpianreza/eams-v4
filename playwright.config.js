import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    timeout: 30_000,
    reporter: [['line']],
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000',
        browserName: 'chromium',
        headless: true,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
});

import { expect, test } from '@playwright/test';

const credentials = { login: 'qa-admin', password: 'Password123!' };

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('Username atau Email').fill(credentials.login);
    await page.locator('#password').fill(credentials.password);
    await page.getByRole('button', { name: /Masuk/ }).click();
    await expect(page).toHaveURL(/\/home$/);
}

function collectBrowserErrors(page) {
    const errors = [];
    page.on('pageerror', error => errors.push(`pageerror: ${error.message}`));
    page.on('console', message => {
        if (message.type() === 'error') errors.push(`console: ${message.text()}`);
    });
    return errors;
}

test('desktop shell persists collapse and wire navigation state', async ({ page }) => {
    const errors = collectBrowserErrors(page);
    await page.setViewportSize({ width: 1440, height: 900 });
    await login(page);

    await expect(page.locator('[data-eams-shell="sidebar"]')).toBeVisible();
    await expect(page.locator('[data-eams-shell="topbar"]')).toBeVisible();
    await expect(page.locator('#main-content')).toBeVisible();

    const collapse = page.getByRole('button', { name: 'Ringkas sidebar' });
    await collapse.click();
    await expect.poll(() => page.evaluate(() => localStorage.getItem('eams-sidebar-collapsed'))).toBe('true');

    await page.evaluate(() => { window.__eamsWireNavigateMarker = 'alive'; });
    await page.locator('a[href$="/compliance/dashboard"]').first().click();
    await expect(page).toHaveURL(/\/compliance\/dashboard$/);
    await expect.poll(() => page.evaluate(() => window.__eamsWireNavigateMarker)).toBe('alive');

    await page.goBack();
    await expect(page).toHaveURL(/\/home$/);
    await page.goForward();
    await expect(page).toHaveURL(/\/compliance\/dashboard$/);
    expect(errors).toEqual([]);
});

test('mobile drawer, theme persistence, and overflow remain stable', async ({ page }) => {
    const errors = collectBrowserErrors(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);

    const menuButton = page.getByRole('button', { name: 'Buka menu' });
    await menuButton.click();
    await expect(menuButton).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('[data-eams-shell="backdrop"]')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(menuButton).toHaveAttribute('aria-expanded', 'false');

    await page.evaluate(() => localStorage.setItem('eams-theme', 'dark'));
    await page.reload();
    await expect(page.locator('html')).toHaveAttribute('data-bs-theme', 'dark');

    const noOverflow = await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
    expect(noOverflow).toBe(true);
    expect(errors).toEqual([]);
});

test('Tailwind and Alpine component interactions work in Chromium', async ({ page }) => {
    const errors = collectBrowserErrors(page);
    await page.setViewportSize({ width: 1280, height: 900 });
    await login(page);
    await page.goto('/__qa/ui-components');
    await expect(page.locator('[data-qa="component-showcase"]')).toBeVisible();

    await page.locator('[data-qa="open-dropdown"]').click();
    await expect(page.getByRole('menu')).toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Menu QA aktif' })).toBeVisible();

    await page.locator('[data-qa="open-modal"]').click();
    await expect(page.getByRole('dialog', { name: 'Modal QA' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('dialog', { name: 'Modal QA' })).toBeHidden();

    await page.locator('[data-qa="open-drawer"]').click();
    await expect(page.getByRole('dialog', { name: 'Drawer QA' })).toBeVisible();
    await page.keyboard.press('Escape');

    await page.locator('[data-qa="open-confirm"]').click();
    const confirmDialog = page.getByRole('alertdialog', { name: 'Konfirmasi QA' });
    await expect(confirmDialog).toBeVisible();
    await expect(confirmDialog.getByText('Konfirmasi browser QA')).toBeVisible();
    await confirmDialog.locator('section').getByRole('button', { name: 'Batal' }).click();

    await page.locator('input[name="qa_photo"]').setInputFiles({
        name: 'qa-image.png',
        mimeType: 'image/png',
        buffer: Buffer.from('browser-qa'),
    });
    await expect(page.getByText('qa-image.png')).toBeVisible();
    await expect(page.getByText('Fallback gambar QA')).toBeVisible();

    await page.locator('[data-qa="show-toast"]').click();
    await expect(page.getByText('QA toast berhasil')).toBeVisible();
    expect(errors).toEqual([]);
});

test('Bootstrap legacy modal still works beside Livewire and Tailwind', async ({ page }) => {
    const errors = collectBrowserErrors(page);
    await page.setViewportSize({ width: 1280, height: 900 });
    await login(page);
    await page.goto('/users');

    await expect(page.getByRole('heading', { name: 'Manajemen User' })).toBeVisible();
    await page.evaluate(() => {
        const modal = document.getElementById('roleModal');
        window.__roleModalShown = new Promise(resolve => modal.addEventListener('shown.bs.modal', () => resolve(true), { once: true }));
    });
    await page.locator('[data-bs-target="#roleModal"]').click();
    await page.evaluate(() => window.__roleModalShown);
    await expect(page.locator('#roleModal.modal.show')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Tambah Role' })).toBeVisible();
    expect(await page.evaluate(() => window.bootstrap.Modal.getInstance(document.getElementById('roleModal')) !== null)).toBe(true);

    await page.evaluate(() => {
        const modal = document.getElementById('roleModal');
        window.__roleModalHidden = new Promise(resolve => modal.addEventListener('hidden.bs.modal', () => resolve(true), { once: true }));
    });
    await page.locator('#roleModal .modal-header [data-bs-dismiss="modal"]').click();
    await page.evaluate(() => window.__roleModalHidden);
    await expect(page.locator('#roleModal.modal.show')).toHaveCount(0);
    expect(errors).toEqual([]);
});

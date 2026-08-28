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
    page.on('console', message => {
        if (message.type() === 'error') {
            errors.push(`console: ${message.text()}`);
        }
    });
    page.on('pageerror', error => {
        errors.push(`pageerror: ${error.stack ?? error.message}`);
    });
    return errors;
}

function expectNoBrowserErrors(errors, checkpoint) {
    expect(errors, `Browser error setelah ${checkpoint}`).toEqual([]);
}

async function expectNoHorizontalOverflow(page, checkpoint) {
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth),
        { message: `Horizontal overflow setelah ${checkpoint}`, timeout: 2_000 },
    ).toBeLessThanOrEqual(1);
}

test('inventory index renders through wire navigation with filters and table', async ({ page }) => {
    const errors = collectBrowserErrors(page);
    await page.setViewportSize({ width: 1280, height: 900 });
    await login(page);

    await page.locator('a[href$="/compliance/inventory"]').first().click();
    await expect(page).toHaveURL(/\/compliance\/inventory$/);

    await expect(page.locator('[data-eams-livewire="compliance-inventory-index"]')).toBeVisible();
    await expect(page.locator('[data-eams-component="table"]')).toBeVisible();
    await expect(page.locator('[data-eams-shell="sidebar"]')).toBeVisible();

    // Filter by status need_repair must keep the shell alive (Livewire update, no reload).
    const search = page.locator('input[name="inventory-search"]');
    await expect(search).toBeVisible();
    const statusSelect = page.locator('select[name="inventory-status"]');
    await statusSelect.selectOption('need_repair');

    // Either rows with Need Repair pill or the filtered empty state renders.
    await expect(page.locator('[data-status="NEED_REPAIR"], [data-eams-component="empty-state"]').first()).toBeVisible();

    await expectNoHorizontalOverflow(page, 'index filter');
    expectNoBrowserErrors(errors, 'inventory index');
});

test('inventory create -> detail -> delete flow works end to end', async ({ page }) => {
    const errors = collectBrowserErrors(page);
    await page.setViewportSize({ width: 1280, height: 900 });
    await login(page);

    const code = `QA-${Date.now().toString().slice(-8)}`;

    // Open create form from index.
    await page.goto('/compliance/inventory');
    await page.locator('a[href$="/compliance/inventory/create"]').first().click();
    await expect(page).toHaveURL(/\/compliance\/inventory\/create$/);

    await expect(page.locator('input[name="photo"]')).toBeVisible();
    await expect(page.locator('select[name="pic_ids"]')).toBeVisible();

    // Fill minimal required fields.
    await page.locator('select[name="inventory_category_id"]').selectOption({ index: 1 });
    await page.locator('select[name="asset_item_type_id"]').selectOption({ index: 1 });
    await page.locator('input[name="asset_code"]').fill(code);
    await page.locator('select[name="status"]').selectOption('good');
    await page.locator('input[name="qty"]').fill('1');
    await page.getByRole('button', { name: /Simpan inventory/ }).click();

    // Redirect back to index with the new row present.
    await expect(page).toHaveURL(/\/compliance\/inventory$/);
    await expect(page.locator(`[data-eams-inventory-row="${code}"]`)).toBeVisible();

    // Open detail via wire navigation (row link).
    await page.locator(`[data-eams-inventory-row="${code}"] a[href*="/detail/"]`).click();
    await expect(page).toHaveURL(/\/compliance\/inventory\/detail\/\d+$/);
    await expect(page.locator('[data-eams-shell="sidebar"]')).toBeVisible();
    await expect(page.getByRole('heading', { name: code })).toBeVisible();
    await expect(page.getByText('QR inventory')).toBeVisible();
    await expect(page.getByText('Foto inventory')).toBeVisible();

    // Go back to index and delete the created inventory via confirm dialog.
    await page.goto('/compliance/inventory');
    await page.locator(`[data-eams-inventory-row="${code}"] button[title^="Hapus"]`).click();
    const dialog = page.locator('[data-eams-component="confirm-dialog"]');
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Ya, hapus' }).click();

    await expect(page.locator(`[data-eams-inventory-row="${code}"]`)).toHaveCount(0);
    expectNoBrowserErrors(errors, 'inventory flow');
});

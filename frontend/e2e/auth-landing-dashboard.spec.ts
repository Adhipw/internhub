import { expect, test, type Page } from '@playwright/test';

const studentEmail = process.env.E2E_STUDENT_EMAIL || 'e2e.student@example.com';
const studentPassword = process.env.E2E_STUDENT_PASSWORD || 'Password123!';

async function loginAsStudent(page: Page) {
    await page.goto('/login');
    await page.locator('#email').fill(studentEmail);
    await page.locator('input[type="password"]').fill(studentPassword);
    await page.getByRole('button', { name: /masuk|login|sign in/i }).click();

    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page.getByRole('heading', { name: /halo|hello/i })).toBeVisible();
}

async function ensureNoLegacyServiceWorkerControlsPage(page: Page) {
    const hasServiceWorker = await page.evaluate(() => 'serviceWorker' in navigator);
    expect(hasServiceWorker).toBe(true);

    await expect.poll(
        () => page.evaluate(() => Boolean(navigator.serviceWorker.controller)),
        { message: 'legacy service worker should not control the page' },
    ).toBe(false);
}

test.describe('authenticated landing dashboard navigation', () => {
    test('logged-in student can return from landing page to dashboard after refresh without legacy service worker redirects', async ({ page }) => {
        await loginAsStudent(page);

        await page.goto('/');
        await expect(page).toHaveURL(/\/$/);
        await expect(page.getByRole('link', { name: /dashboard/i }).first()).toBeVisible();

        await page.getByRole('link', { name: /dashboard/i }).first().click();
        await expect(page).toHaveURL(/\/dashboard$/);
        await expect(page).not.toHaveURL(/\/login/);
        await expect(page.getByRole('heading', { name: /halo|hello/i })).toBeVisible();

        await page.reload({ waitUntil: 'networkidle' });
        await expect(page).toHaveURL(/\/dashboard$/);
        await expect(page).not.toHaveURL(/\/login/);
        await expect(page.getByRole('heading', { name: /halo|hello/i })).toBeVisible();

        await ensureNoLegacyServiceWorkerControlsPage(page);

        await page.goto('/');
        await expect(page.getByRole('link', { name: /dashboard/i }).first()).toBeVisible();

        await page.getByRole('link', { name: /dashboard/i }).first().click();
        await expect(page).toHaveURL(/\/dashboard$/);
        await expect(page).not.toHaveURL(/\/login/);

        await page.reload({ waitUntil: 'networkidle' });
        await expect(page).toHaveURL(/\/dashboard$/);
        await expect(page).not.toHaveURL(/\/login/);
    });
});

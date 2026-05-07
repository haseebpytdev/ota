/**
 * Full customer flow requires the Laravel app to run with E2E_FORCE_MOCK_SUPPLIER=true
 * in .env (local/testing only). The server ignores the flag in production.
 */
import { test, expect, Page } from '@playwright/test';

async function pauseForVideo(page: Page, ms = 700) {
  await page.waitForTimeout(ms);
}

test.describe('Asif Travels OTA visual walkthrough', () => {
  test('customer booking walkthrough', async ({ page }, testInfo) => {
    const isMobile = testInfo.project.name === 'mobile-chrome';
    if (isMobile) {
      await page.setViewportSize({ width: 390, height: 844 });
    }

    await page.goto('/');
    await expect(page.locator('.ota-hero')).toBeVisible();
    await expect(page.getByText('Asif Travels').first()).toBeVisible();
    await pauseForVideo(page, 1000);

    await page.goto('/flights/search');
    await expect(
      page.getByRole('heading', { name: /book your next flight|search flights|flight search/i }).first(),
    ).toBeVisible();
    await pauseForVideo(page, 600);

    await page.getByRole('textbox', { name: /^from$/i }).fill('LHE');
    await pauseForVideo(page, 400);
    await page.keyboard.press('Escape');

    await page.getByRole('textbox', { name: /^to$/i }).fill('DXB');
    await pauseForVideo(page, 400);
    await page.keyboard.press('Escape');

    const futureDate = '2026-08-15';
    await page.locator('input[type="date"]').first().fill(futureDate);

    const cabin = page.getByLabel(/cabin/i).first();
    if (await cabin.count()) {
      await cabin.selectOption({ label: /Economy/i }).catch(async () => {
        await cabin.selectOption('economy');
      });
    }

    await pauseForVideo(page, 500);
    // Submit buttons trigger full navigation; Playwright may wait indefinitely for
    // "scheduled navigations" even after the results page has painted — decouple click from navigation wait.
    await page.getByRole('button', { name: /search flights/i }).click({ noWaitAfter: true });
    await page.waitForURL(/\/flights\/results/);
    await expect(page.getByRole('heading', { name: /available flights/i })).toBeVisible();
    await pauseForVideo(page, 1200);

    if (isMobile) {
      // Backdrop can sit above the open control in the tab order; hide it for a reliable open click.
      await page.locator('[data-filter-backdrop]').evaluate((el) => {
        (el as HTMLElement).style.setProperty('display', 'none');
        (el as HTMLElement).style.setProperty('pointer-events', 'none');
      });
      await page.getByRole('button', { name: /filter results/i }).click();
      await expect(page.locator('[data-filter-drawer]')).toBeVisible();
      await page.getByRole('button', { name: /close/i }).click();
      await expect(page.locator('[data-filter-drawer]')).toBeHidden();
      await page.getByRole('button', { name: /filter results/i }).click();
      await expect(page.locator('[data-filter-drawer]')).toBeVisible();
      await page.locator('#ota-filter-sort').selectOption('cheapest');
      await page.getByRole('button', { name: /apply filters/i }).click();
      await pauseForVideo(page, 800);
    }

    const firstDetails = page.getByRole('button', { name: /flight details/i }).first();
    if (await firstDetails.isVisible()) {
      await firstDetails.click();
      await pauseForVideo(page, 1000);
    }

    const bookLink = page.getByRole('link', { name: /^book now$/i }).first();
    await expect(bookLink).toBeVisible();
    await bookLink.click({ noWaitAfter: true });
    await page.waitForURL(/\/booking\/passengers/);
    await expect(page.getByRole('heading', { name: /^checkout$/i })).toBeVisible();
    await pauseForVideo(page, 800);

    if (!isMobile) {
      const signInToContinue = page.getByRole('link', { name: /sign in to continue/i }).first();
      if (await signInToContinue.count()) {
        await signInToContinue.click();
        await page.waitForURL(/\/login/);
        await page.getByLabel(/username or email/i).fill('customer@ota.demo');
        await page.getByLabel(/password/i).fill('password');
        await page.getByRole('button', { name: /log in|sign in/i }).click({ noWaitAfter: true });
        await page.waitForURL(/\/booking\/passengers/);
        await expect(page.getByRole('heading', { name: /^checkout$/i })).toBeVisible();
        await pauseForVideo(page, 600);
      }
    }

    await page.getByLabel(/title/i).selectOption({ label: /Mr/i }).catch(() => {});
    await page.getByLabel(/first name/i).fill('Test');
    await page.getByLabel(/last name/i).fill('Passenger');

    const dob = page.getByLabel(/date of birth/i).first();
    if (await dob.count()) await dob.fill('1995-01-01');

    const nationality = page.getByLabel(/nationality/i).first();
    if (await nationality.count()) await nationality.fill('PK');

    const gender = page.getByLabel(/gender/i).first();
    if (await gender.count()) {
      await gender.selectOption({ label: /male/i }).catch(async () => {
        await gender.selectOption('M').catch(() => {});
      });
    }

    const passport = page.getByLabel(/passport number/i).first();
    if (await passport.count()) await passport.fill('AB1234567');

    const passportCountry = page.getByLabel(/issuing country/i).first();
    if (await passportCountry.count()) await passportCountry.fill('PK');

    const passportExpiry = page.getByLabel(/passport expiry/i).first();
    if (await passportExpiry.count()) await passportExpiry.fill('2032-12-31');

    await page.getByRole('textbox', { name: /^email$/i }).fill('test.customer@example.com');
    await page.getByRole('textbox', { name: /^mobile$/i }).fill('+923001234567');

    const country = page.getByLabel(/country/i).first();
    if (await country.count()) await country.fill('Pakistan');

    await pauseForVideo(page, 500);

    await page.getByRole('button', { name: /continue to review/i }).click({ noWaitAfter: true });
    await page.waitForURL(/\/booking\/review/, { timeout: 120_000, waitUntil: 'commit' });

    await expect(page.getByRole('heading', { name: /review your booking/i })).toBeVisible();
    await pauseForVideo(page, 1000);

    await page.getByRole('button', { name: /request booking/i }).click({ noWaitAfter: true });
    await page.waitForURL(/\/booking\/confirmation/, { timeout: 120_000, waitUntil: 'commit' });

    await expect(page.getByRole('heading', { name: /booking request received/i })).toBeVisible();
    await pauseForVideo(page, 1500);
  });

  test('admin walkthrough', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByRole('heading', { name: /sign in to asif travels/i })).toBeVisible();

    await page.getByLabel(/email/i).fill('admin@ota.demo');
    await page.getByLabel(/password/i).fill('password');
    await page.getByRole('button', { name: /log in|sign in/i }).click({ noWaitAfter: true });

    await expect(page).toHaveURL(/\/admin(?:$|[/?#])/);
    await expect(page.getByText(/dashboard|operator/i).first()).toBeVisible();
    await pauseForVideo(page, 1000);

    await page.goto('/admin/api-settings');
    await expect(page.getByRole('heading', { name: /api settings/i })).toBeVisible();
    await pauseForVideo(page, 1200);

    await page.goto('/admin/bookings');
    await expect(page.getByRole('heading', { name: /bookings management/i })).toBeVisible();
    await pauseForVideo(page, 1200);
    const firstBookingDetails = page.locator('a[href*="/admin/bookings/"]').first();
    if (await firstBookingDetails.count()) {
      await firstBookingDetails.click({ noWaitAfter: true });
      await page.waitForURL(/\/admin\/bookings\/\d+/);
      await expect(page.locator('[data-booking-pipeline-bar]')).toBeVisible();
      await pauseForVideo(page, 1200);
    }

    await page.goto('/admin/reports');
    await expect(page.getByRole('heading', { name: /reports/i })).toBeVisible();
    await pauseForVideo(page, 1200);
  });

  test('auth and registration walkthrough', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByRole('heading', { name: /sign in to asif travels/i })).toBeVisible();
    await pauseForVideo(page, 800);

    await page.goto('/register');
    await expect(page.getByRole('heading', { name: /create your asif travels account/i })).toBeVisible();
    await pauseForVideo(page, 800);

    await page.goto('/agent/register');
    await expect(page.getByRole('heading', { name: /join the asif travels agent network/i })).toBeVisible();
    await pauseForVideo(page, 1000);

    await page.goto('/agent/register/apply');
    await expect(page.getByRole('heading', { name: /agent signup application/i })).toBeVisible();
    await pauseForVideo(page, 1200);

    await page.goto('/lookup-booking');
    await expect(page.getByRole('heading', { name: /lookup your booking/i })).toBeVisible();
    await pauseForVideo(page, 900);
  });
});

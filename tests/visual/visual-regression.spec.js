/**
 * AUD-01: Visual Regression Testing Suite
 * 
 * This test suite creates visual baselines and performs regression testing
 * as required by ROADMAP-FINAL.md Section B - AUD-01.
 * 
 * Visual Coverage:
 * - Login page and authentication flow
 * - Dashboard layouts (admin and partner)
 * - Customer management interface
 * - Invoice creation and editing
 * - Payment processing screens
 * - Accountant console and company switching
 * - Mobile responsive layouts
 * - Macedonia-specific UI elements
 * - Error states and loading screens
 * 
 * Browser Coverage:
 * - Chrome/Chromium (primary)
 * - Firefox
 * - Safari/WebKit
 * - Mobile Chrome (Pixel 5)
 * - Mobile Safari (iPhone 12)
 * 
 * Success Criteria:
 * - Visual baselines established for all key screens
 * - Cross-browser compatibility validated
 * - Mobile responsiveness verified
 * - Macedonia localization preserved
 * - Partner console visual integrity maintained
 * 
 * @version 1.0.0
 * @created 2025-07-26 - AUD-01 implementation
 * @author Claude Code - Based on ROADMAP-FINAL requirements
 */

import { test, expect } from '@playwright/test';

// Helper function for login
async function loginAsAdmin(page) {
  await page.goto('/admin/auth/login');
  await page.fill('input[name="email"]', 'admin@invoiceshelf.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/dashboard');
}

async function loginAsPartner(page) {
  await page.goto('/admin/auth/login');
  await page.fill('input[name="email"]', 'partner@accounting.mk');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/dashboard');
}

// Wait for page to be fully loaded
async function waitForPageLoad(page) {
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1000); // Allow animations to complete
}

test.describe('Authentication & Login', () => {
  test('login page visual baseline', async ({ page }) => {
    await page.goto('/admin/auth/login');
    await waitForPageLoad(page);
    
    // Hide dynamic elements that might cause flakiness
    await page.addStyleTag({
      content: `
        .loading-spinner { opacity: 0 !important; }
        .toast-notification { display: none !important; }
      `
    });
    
    await expect(page).toHaveScreenshot('login-page.png');
  });

  test('login form validation errors', async ({ page }) => {
    await page.goto('/admin/auth/login');
    await page.click('button[type="submit"]'); // Submit without credentials
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('login-validation-errors.png');
  });
});

test.describe('Admin Dashboard', () => {
  test('admin dashboard layout', async ({ page }) => {
    await loginAsAdmin(page);
    await waitForPageLoad(page);
    
    // Hide dynamic content
    await page.addStyleTag({
      content: `
        .timestamp { visibility: hidden !important; }
        .real-time-data { opacity: 0 !important; }
        .loading-skeleton { display: none !important; }
      `
    });
    
    await expect(page).toHaveScreenshot('admin-dashboard.png');
  });

  test('admin navigation menu', async ({ page }) => {
    await loginAsAdmin(page);
    await page.hover('[data-cy="main-navigation"], .navigation-menu, nav');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('admin-navigation.png');
  });

  test('admin dashboard mobile layout', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 }); // iPhone SE size
    await loginAsAdmin(page);
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('admin-dashboard-mobile.png');
  });
});

test.describe('Customer Management', () => {
  test('customers list page', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/customers');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('customers-list.png');
  });

  test('customer creation form', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/customers');
    await page.click('[data-cy="add-customer"], .btn:has-text("Add"), button:has-text("Add")');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('customer-create-form.png');
  });

  test('customer form with Macedonia data', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/customers');
    await page.click('[data-cy="add-customer"], .btn:has-text("Add"), button:has-text("Add")');
    
    // Fill form with Macedonia-specific data
    await page.fill('input[name="name"]', 'Македонска Компанија ДОО');
    await page.fill('input[name="email"]', 'kontakt@kompanija.mk');
    await page.fill('input[name="phone"]', '+38970123456');
    await page.fill('input[name="tax_id"]', 'MK4080003501411');
    
    await waitForPageLoad(page);
    await expect(page).toHaveScreenshot('customer-macedonia-form.png');
  });
});

test.describe('Invoice Management', () => {
  test('invoices list page', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/invoices');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('invoices-list.png');
  });

  test('invoice creation form', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/invoices');
    await page.click('[data-cy="add-invoice"], .btn:has-text("Add"), button:has-text("Add")');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('invoice-create-form.png');
  });

  test('invoice with items and taxes', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/invoices');
    await page.click('[data-cy="add-invoice"], .btn:has-text("Add"), button:has-text("Add")');
    
    // Add an item
    await page.click('[data-cy="add-item"], .btn:has-text("Add Item"), button:has-text("Add")');
    await page.fill('input[name*="name"], input[placeholder*="name"]', 'Macedonia Consulting Service');
    await page.fill('input[name*="quantity"]', '1');
    await page.fill('input[name*="price"]', '2500.00');
    
    await waitForPageLoad(page);
    await expect(page).toHaveScreenshot('invoice-with-items.png');
  });

  test('invoice preview/print layout', async ({ page }) => {
    await loginAsAdmin(page);
    // Navigate to an existing invoice (would need to be seeded)
    await page.goto('/admin/invoices/1', { waitUntil: 'networkidle' });
    
    if (await page.locator('body').textContent().then(text => text.includes('404') || text.includes('Not Found'))) {
      // Skip if no test invoice exists
      test.skip();
    }
    
    await page.click('[data-cy="preview"], .btn:has-text("Preview"), button:has-text("Preview")');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('invoice-preview.png');
  });
});

test.describe('Partner/Accountant Console', () => {
  test('partner dashboard layout', async ({ page }) => {
    await loginAsPartner(page);
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('partner-dashboard.png');
  });

  test('accountant console main view', async ({ page }) => {
    await loginAsPartner(page);
    await page.goto('/admin/console');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('accountant-console-main.png');
  });

  test('company switching interface', async ({ page }) => {
    await loginAsPartner(page);
    await page.goto('/admin/console');
    
    // Look for company switcher
    const companySwitcher = await page.locator('[data-cy="company-switcher"], .company-switcher, .dropdown-toggle').first();
    if (await companySwitcher.isVisible()) {
      await companySwitcher.click();
      await waitForPageLoad(page);
      
      await expect(page).toHaveScreenshot('company-switching-dropdown.png');
    } else {
      // If no dropdown, capture the company cards/list
      await expect(page).toHaveScreenshot('company-selection-cards.png');
    }
  });

  test('partner console mobile layout', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await loginAsPartner(page);
    await page.goto('/admin/console');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('partner-console-mobile.png');
  });
});

test.describe('Payment Processing', () => {
  test('payment creation form', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/payments');
    await page.click('[data-cy="add-payment"], .btn:has-text("Add"), button:has-text("Add")');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('payment-create-form.png');
  });

  test('payment methods selection', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/payments');
    await page.click('[data-cy="add-payment"], .btn:has-text("Add"), button:has-text("Add")');
    
    // Click on payment method dropdown
    const methodSelect = await page.locator('select[name*="method"], [data-cy="payment-method"]').first();
    if (await methodSelect.isVisible()) {
      await methodSelect.click();
      await waitForPageLoad(page);
    }
    
    await expect(page).toHaveScreenshot('payment-methods-selection.png');
  });
});

test.describe('System Settings & Configuration', () => {
  test('company settings page', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/settings/company');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('company-settings.png');
  });

  test('tax configuration with Macedonia rates', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/settings/tax-types');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('tax-configuration.png');
  });
});

test.describe('Error States & Loading Screens', () => {
  test('404 error page', async ({ page }) => {
    await page.goto('/admin/non-existent-page');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('404-error-page.png');
  });

  test('network error state', async ({ page }) => {
    // Simulate network failure
    await page.route('**/api/**', route => route.abort());
    
    await loginAsAdmin(page);
    await page.goto('/admin/customers');
    await waitForPageLoad(page);
    
    await expect(page).toHaveScreenshot('network-error-state.png');
  });

  test('loading skeleton states', async ({ page }) => {
    // Intercept API calls to slow them down
    await page.route('**/api/v1/customers', async route => {
      await new Promise(resolve => setTimeout(resolve, 2000));
      route.continue();
    });
    
    await loginAsAdmin(page);
    const customerPagePromise = page.goto('/admin/customers');
    
    // Capture loading state
    await page.waitForSelector('.loading, .skeleton, [data-cy="loading"]', { timeout: 5000 }).catch(() => {});
    await expect(page).toHaveScreenshot('loading-skeleton-state.png');
    
    await customerPagePromise;
  });
});

test.describe('Cross-Browser Compatibility', () => {
  ['chromium', 'firefox', 'webkit'].forEach(browserName => {
    test(`dashboard consistency in ${browserName}`, async ({ page, browserName: currentBrowser }) => {
      test.skip(currentBrowser !== browserName);
      
      await loginAsAdmin(page);
      await waitForPageLoad(page);
      
      await expect(page).toHaveScreenshot(`dashboard-${browserName}.png`);
    });
  });
});

test.describe('Responsive Design Validation', () => {
  const viewports = [
    { name: 'desktop', width: 1280, height: 720 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'mobile', width: 375, height: 667 },
    { name: 'mobile-landscape', width: 667, height: 375 }
  ];

  viewports.forEach(viewport => {
    test(`responsive layout - ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await loginAsAdmin(page);
      await waitForPageLoad(page);
      
      await expect(page).toHaveScreenshot(`responsive-${viewport.name}.png`);
    });
  });
});

test.describe('Macedonia Localization', () => {
  test('cyrillic text rendering', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/customers');
    await page.click('[data-cy="add-customer"], .btn:has-text("Add"), button:has-text("Add")');
    
    // Fill with Cyrillic text
    await page.fill('input[name="name"]', 'Македонски Тест Клиент');
    await page.fill('input[name*="address"], input[placeholder*="address"]', 'бул. Македонија 15');
    await page.fill('input[name="city"], input[placeholder*="city"]', 'Скопје');
    
    await waitForPageLoad(page);
    await expect(page).toHaveScreenshot('cyrillic-text-rendering.png');
  });

  test('macedonia currency formatting', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/invoices');
    await page.click('[data-cy="add-invoice"], .btn:has-text("Add"), button:has-text("Add")');
    
    // Add item with MKD pricing
    await page.click('[data-cy="add-item"], .btn:has-text("Add Item"), button:has-text("Add")');
    await page.fill('input[name*="price"]', '2500.00');
    
    await waitForPageLoad(page);
    await expect(page).toHaveScreenshot('macedonia-currency-formatting.png');
  });
});


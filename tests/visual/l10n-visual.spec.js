/**
 * QA-L10N-02: Playwright Screenshot Comparison Tests for mk/sq Languages
 * 
 * This test suite creates visual regression tests specifically for 
 * Macedonian (mk) and Albanian (sq) localizations as required by 
 * ROADMAP-L10N.md Section E - QA-L10N-02.
 * 
 * Visual Coverage:
 * - Login page in mk/sq languages
 * - Dashboard with AI Insights widget
 * - Sidebar with Migration Wizard link
 * - Tax menu with VAT Return action
 * - Settings pages with localized content
 * - Key UI components with Facturino branding
 * 
 * Test Strategy:
 * - Switch to mk/sq locale programmatically
 * - Capture screenshots of critical pages
 * - Ensure Cyrillic text rendering (Macedonian)
 * - Verify Albanian text rendering  
 * - Test new feature UI elements in both languages
 * 
 * Success Criteria:
 * - Baseline screenshots captured for mk/sq
 * - UI layout preserved across languages
 * - Branding consistency maintained
 * - New features visible and localized
 * 
 * @version 1.0.0
 * @created 2025-07-26 - QA-L10N-02 implementation
 * @author Claude Code - Based on ROADMAP-L10N requirements
 */

import { test, expect } from '@playwright/test';

// Helper function for admin login
async function loginAsAdmin(page) {
  await page.goto('/admin/auth/login');
  await page.fill('input[name="email"]', 'admin@invoiceshelf.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/dashboard');
}

// Helper function to change language via user settings
async function changeLanguage(page, locale) {
  // Navigate to user settings
  await page.goto('/admin/settings/account');
  await page.waitForLoadState('networkidle');
  
  // Find and select language dropdown
  const languageSelect = page.locator('select[name="language"], select[name*="language"]').first();
  if (await languageSelect.isVisible()) {
    await languageSelect.selectOption(locale);
    
    // Save settings
    const saveButton = page.locator('button:has-text("Save"), button[type="submit"]').first();
    if (await saveButton.isVisible()) {
      await saveButton.click();
      await page.waitForTimeout(2000); // Allow language change to process
    }
  } else {
    // Alternative: Use API call to change language
    await page.evaluate(async (locale) => {
      if (window.axios) {
        try {
          await window.axios.put('/api/v1/me/settings', {
            settings: { language: locale }
          });
          
          // Update i18n locale if available
          if (window.i18n && window.i18n.global) {
            window.i18n.global.locale.value = locale;
          }
        } catch (error) {
          console.log('Language change via API failed:', error);
        }
      }
    }, locale);
    
    // Reload page to apply changes
    await page.reload();
    await page.waitForLoadState('networkidle');
  }
}

// Wait for page to be fully loaded
async function waitForPageLoad(page) {
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500); // Allow animations and i18n to complete
}

// Hide dynamic content that might cause test flakiness
async function hideDynamicContent(page) {
  await page.addStyleTag({
    content: `
      .loading-spinner { opacity: 0 !important; }
      .toast-notification { display: none !important; }
      .timestamp { visibility: hidden !important; }
      .real-time-data { opacity: 0 !important; }
      .loading-skeleton { display: none !important; }
      .animation { animation: none !important; }
    `
  });
}

test.describe('Macedonian (mk) Language Screenshots', () => {
  test('login page in Macedonian', async ({ page }) => {
    await page.goto('/admin/auth/login');
    
    // Set language to Macedonian via localStorage or direct API call
    await page.evaluate(() => {
      if (window.i18n && window.i18n.global) {
        window.i18n.global.locale.value = 'mk';
      }
      localStorage.setItem('language', 'mk');
    });
    
    await page.reload();
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    await expect(page).toHaveScreenshot('login-page-mk.png');
  });

  test('dashboard with AI Insights in Macedonian', async ({ page }) => {
    await loginAsAdmin(page);
    await changeLanguage(page, 'mk');
    
    // Navigate to dashboard
    await page.goto('/admin/dashboard');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    // Ensure AI Insights widget is visible if it exists
    const aiInsights = page.locator('[data-cy="ai-insights"], .ai-insights-widget').first();
    if (await aiInsights.isVisible()) {
      await aiInsights.scrollIntoViewIfNeeded();
    }
    
    await expect(page).toHaveScreenshot('dashboard-ai-insights-mk.png');
  });

  test('sidebar with Migration Wizard in Macedonian', async ({ page }) => {
    await loginAsAdmin(page);
    await changeLanguage(page, 'mk');
    
    await page.goto('/admin/dashboard');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    // Open sidebar if it's collapsible
    const sidebarToggle = page.locator('[data-cy="sidebar-toggle"], .sidebar-toggle, .hamburger-menu').first();
    if (await sidebarToggle.isVisible()) {
      await sidebarToggle.click();
      await page.waitForTimeout(500);
    }
    
    // Focus on sidebar area
    const sidebar = page.locator('aside, .sidebar, nav[role="navigation"]').first();
    if (await sidebar.isVisible()) {
      await sidebar.scrollIntoViewIfNeeded();
      
      // Look for Migration Wizard link
      const migrationLink = page.locator('a:has-text("Migration"), a:has-text("Wizard"), [href*="import"], [href*="migration"]').first();
      if (await migrationLink.isVisible()) {
        await migrationLink.scrollIntoViewIfNeeded();
      }
    }
    
    await expect(page).toHaveScreenshot('sidebar-migration-wizard-mk.png');
  });

  test('tax menu with VAT Return in Macedonian', async ({ page }) => {
    await loginAsAdmin(page);
    await changeLanguage(page, 'mk');
    
    // Navigate to taxes page
    await page.goto('/admin/settings/tax-types');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    // Look for tax dropdown or VAT return action
    const taxDropdown = page.locator('[data-cy="tax-dropdown"], .dropdown-toggle, .tax-actions').first();
    if (await taxDropdown.isVisible()) {
      await taxDropdown.click();
      await page.waitForTimeout(500);
    }
    
    // Alternative: Check if VAT return is directly visible
    const vatReturn = page.locator('button:has-text("VAT"), a:has-text("Return"), [href*="vat"]').first();
    if (await vatReturn.isVisible()) {
      await vatReturn.scrollIntoViewIfNeeded();
    }
    
    await expect(page).toHaveScreenshot('tax-menu-vat-return-mk.png');
  });

  test('settings page in Macedonian', async ({ page }) => {
    await loginAsAdmin(page);
    await changeLanguage(page, 'mk');
    
    await page.goto('/admin/settings/account');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    await expect(page).toHaveScreenshot('settings-page-mk.png');
  });

  test('company settings with Facturino branding in Macedonian', async ({ page }) => {
    await loginAsAdmin(page);
    await changeLanguage(page, 'mk');
    
    await page.goto('/admin/settings/company');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    await expect(page).toHaveScreenshot('company-settings-facturino-mk.png');
  });
});

test.describe('Albanian (sq) Language Screenshots', () => {
  test('login page in Albanian', async ({ page }) => {
    await page.goto('/admin/auth/login');
    
    // Set language to Albanian
    await page.evaluate(() => {
      if (window.i18n && window.i18n.global) {
        window.i18n.global.locale.value = 'sq';
      }
      localStorage.setItem('language', 'sq');
    });
    
    await page.reload();
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    await expect(page).toHaveScreenshot('login-page-sq.png');
  });

  test('dashboard with AI Insights in Albanian', async ({ page }) => {
    await loginAsAdmin(page);
    await changeLanguage(page, 'sq');
    
    await page.goto('/admin/dashboard');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    // Ensure AI Insights widget is visible if it exists
    const aiInsights = page.locator('[data-cy="ai-insights"], .ai-insights-widget').first();
    if (await aiInsights.isVisible()) {
      await aiInsights.scrollIntoViewIfNeeded();
    }
    
    await expect(page).toHaveScreenshot('dashboard-ai-insights-sq.png');
  });

  test('sidebar with Migration Wizard in Albanian', async ({ page }) => {
    await loginAsAdmin(page);
    await changeLanguage(page, 'sq');
    
    await page.goto('/admin/dashboard');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    // Open sidebar if it's collapsible
    const sidebarToggle = page.locator('[data-cy="sidebar-toggle"], .sidebar-toggle, .hamburger-menu').first();
    if (await sidebarToggle.isVisible()) {
      await sidebarToggle.click();
      await page.waitForTimeout(500);
    }
    
    // Focus on sidebar area
    const sidebar = page.locator('aside, .sidebar, nav[role="navigation"]').first();
    if (await sidebar.isVisible()) {
      await sidebar.scrollIntoViewIfNeeded();
      
      // Look for Migration Wizard link
      const migrationLink = page.locator('a:has-text("Migration"), a:has-text("Wizard"), [href*="import"], [href*="migration"]').first();
      if (await migrationLink.isVisible()) {
        await migrationLink.scrollIntoViewIfNeeded();
      }
    }
    
    await expect(page).toHaveScreenshot('sidebar-migration-wizard-sq.png');
  });

  test('tax menu with VAT Return in Albanian', async ({ page }) => {
    await loginAsAdmin(page);
    await changeLanguage(page, 'sq');
    
    await page.goto('/admin/settings/tax-types');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    // Look for tax dropdown or VAT return action
    const taxDropdown = page.locator('[data-cy="tax-dropdown"], .dropdown-toggle, .tax-actions').first();
    if (await taxDropdown.isVisible()) {
      await taxDropdown.click();
      await page.waitForTimeout(500);
    }
    
    // Alternative: Check if VAT return is directly visible
    const vatReturn = page.locator('button:has-text("VAT"), a:has-text("Return"), [href*="vat"]').first();
    if (await vatReturn.isVisible()) {
      await vatReturn.scrollIntoViewIfNeeded();
    }
    
    await expect(page).toHaveScreenshot('tax-menu-vat-return-sq.png');
  });

  test('settings page in Albanian', async ({ page }) => {
    await loginAsAdmin(page);
    await changeLanguage(page, 'sq');
    
    await page.goto('/admin/settings/account');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    await expect(page).toHaveScreenshot('settings-page-sq.png');
  });

  test('company settings with Facturino branding in Albanian', async ({ page }) => {
    await loginAsAdmin(page);
    await changeLanguage(page, 'sq');
    
    await page.goto('/admin/settings/company');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    await expect(page).toHaveScreenshot('company-settings-facturino-sq.png');
  });
});

test.describe('Cross-Language Comparison', () => {
  test('navigation consistency between mk and sq', async ({ page }) => {
    // First capture in Macedonian
    await loginAsAdmin(page);
    await changeLanguage(page, 'mk');
    await page.goto('/admin/dashboard');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    const sidebar = page.locator('aside, .sidebar, nav[role="navigation"]').first();
    if (await sidebar.isVisible()) {
      await sidebar.scrollIntoViewIfNeeded();
    }
    
    await expect(page).toHaveScreenshot('navigation-mk.png');
    
    // Then capture in Albanian
    await changeLanguage(page, 'sq');
    await page.goto('/admin/dashboard');
    await waitForPageLoad(page);
    await hideDynamicContent(page);
    
    if (await sidebar.isVisible()) {
      await sidebar.scrollIntoViewIfNeeded();
    }
    
    await expect(page).toHaveScreenshot('navigation-sq.png');
  });

  test('branding consistency across languages', async ({ page }) => {
    // Test Facturino branding in both languages
    const languages = ['mk', 'sq'];
    
    for (const lang of languages) {
      await loginAsAdmin(page);
      await changeLanguage(page, lang);
      await page.goto('/admin/dashboard');
      await waitForPageLoad(page);
      await hideDynamicContent(page);
      
      // Focus on header/branding area
      const header = page.locator('header, .app-header, .main-header').first();
      if (await header.isVisible()) {
        await header.scrollIntoViewIfNeeded();
      }
      
      await expect(page).toHaveScreenshot(`branding-consistency-${lang}.png`);
    }
  });
});


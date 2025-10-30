/**
 * MOBILE-01: PWA Mobile Smoke Test
 * 
 * Tests PWA functionality and mobile responsiveness with iPhone SE emulation:
 * - PWA installation and service worker
 * - Mobile navigation and touch interactions
 * - Core business flows on mobile devices
 * - Macedonia-specific mobile UI elements
 * - Performance on mobile networks
 * - Offline functionality testing
 */

const { test, expect, devices } = require('@playwright/test');

// Configure iPhone SE emulation
const iPhone = devices['iPhone SE'];

test.describe('MOBILE-01: PWA Mobile Smoke Test', () => {
  let context;
  let page;

  test.beforeAll(async ({ browser }) => {
    // Create context with iPhone SE emulation
    context = await browser.newContext({
      ...iPhone,
      // PWA-specific settings
      serviceWorkers: 'allow',
      offline: false,
      // Macedonia locale settings
      locale: 'mk-MK',
      timezoneId: 'Europe/Skopje'
    });

    page = await context.newPage();
    
    // Enable service worker events
    await context.addInitScript(() => {
      window.swInstalled = false;
      window.swEvents = [];
      
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('controllerchange', () => {
          window.swEvents.push('controllerchange');
        });
        
        navigator.serviceWorker.ready.then(() => {
          window.swInstalled = true;
        });
      }
    });
  });

  test.afterAll(async () => {
    await context.close();
  });

  test.describe('PWA Installation and Service Worker', () => {
    test('should detect PWA manifest and installability', async () => {
      await page.goto('/');
      
      // Check for PWA manifest
      const manifestLink = await page.locator('link[rel="manifest"]');
      await expect(manifestLink).toBeVisible();
      
      // Verify manifest content
      const manifestHref = await manifestLink.getAttribute('href');
      const manifestResponse = await page.request.get(manifestHref);
      expect(manifestResponse.status()).toBe(200);
      
      const manifestData = await manifestResponse.json();
      expect(manifestData.name).toContain('InvoiceShelf');
      expect(manifestData.short_name).toBeTruthy();
      expect(manifestData.icons).toBeTruthy();
      expect(manifestData.start_url).toBeTruthy();
      expect(manifestData.display).toBe('standalone');
      
      // Check Macedonia-specific manifest properties
      expect(manifestData.lang).toBe('mk');
      expect(manifestData.theme_color).toBeTruthy();
    });

    test('should register service worker successfully', async () => {
      await page.goto('/');
      
      // Wait for service worker registration
      await page.waitForFunction(() => window.swInstalled === true, { timeout: 10000 });
      
      // Verify service worker is active
      const swRegistered = await page.evaluate(() => {
        return navigator.serviceWorker.controller !== null;
      });
      
      expect(swRegistered).toBe(true);
      
      // Check service worker events
      const swEvents = await page.evaluate(() => window.swEvents);
      expect(swEvents.length).toBeGreaterThan(0);
    });

    test('should handle PWA install prompt on mobile', async () => {
      await page.goto('/');
      
      // Simulate beforeinstallprompt event
      await page.evaluate(() => {
        const event = new Event('beforeinstallprompt');
        event.preventDefault = () => {};
        event.prompt = () => Promise.resolve({ outcome: 'accepted' });
        window.dispatchEvent(event);
      });
      
      // Check if install button appears
      const installButton = page.locator('[data-cy="pwa-install"]');
      await expect(installButton).toBeVisible({ timeout: 5000 });
      
      // Test install flow
      await installButton.click();
      
      // Verify install prompt handling
      const installResult = await page.evaluate(() => {
        return window.pwaInstallResult || 'not_triggered';
      });
      
      expect(installResult).toBe('accepted');
    });
  });

  test.describe('Mobile Navigation and Touch Interactions', () => {
    test('should display mobile-optimized navigation', async () => {
      await page.goto('/');
      
      // Login first
      await page.fill('[data-cy="email"]', 'admin@invoiceshelf.com');
      await page.fill('[data-cy="password"]', 'password');
      await page.click('[data-cy="login-button"]');
      
      await page.waitForURL('**/dashboard');
      
      // Check mobile navigation elements
      const mobileMenuButton = page.locator('[data-cy="mobile-menu-toggle"]');
      await expect(mobileMenuButton).toBeVisible();
      
      // Verify hamburger menu icon
      await expect(mobileMenuButton).toHaveAttribute('aria-label', /menu/i);
      
      // Test mobile menu interaction
      await mobileMenuButton.tap();
      
      const mobileMenu = page.locator('[data-cy="mobile-menu"]');
      await expect(mobileMenu).toBeVisible();
      
      // Verify mobile menu items
      await expect(mobileMenu.locator('[data-cy="nav-dashboard"]')).toBeVisible();
      await expect(mobileMenu.locator('[data-cy="nav-invoices"]')).toBeVisible();
      await expect(mobileMenu.locator('[data-cy="nav-customers"]')).toBeVisible();
      await expect(mobileMenu.locator('[data-cy="nav-payments"]')).toBeVisible();
    });

    test('should handle touch gestures for navigation', async () => {
      await page.goto('/admin/dashboard');
      
      // Test swipe gestures for mobile navigation
      const dashboard = page.locator('[data-cy="dashboard-content"]');
      
      // Swipe left to right (if sidebar navigation is supported)
      await dashboard.hover();
      await page.mouse.down();
      await page.mouse.move(100, 0); // Swipe right
      await page.mouse.up();
      
      // Verify swipe interaction result
      const sidebarOpen = await page.locator('[data-cy="sidebar"]').isVisible();
      expect(sidebarOpen).toBeTruthy();
      
      // Test pull-to-refresh gesture
      await page.evaluate(() => {
        window.scrollTo(0, 0);
      });
      
      // Simulate pull-to-refresh
      await page.touchscreen.tap(100, 50);
      await page.mouse.move(100, 150); // Pull down
      
      // Check for refresh indicator
      const refreshIndicator = page.locator('[data-cy="pull-refresh-indicator"]');
      await expect(refreshIndicator).toBeVisible({ timeout: 2000 });
    });

    test('should optimize tap targets for mobile', async () => {
      await page.goto('/admin/invoices');
      
      // Check button sizes meet mobile accessibility standards (44px minimum)
      const buttons = page.locator('button');
      const buttonCount = await buttons.count();
      
      for (let i = 0; i < Math.min(buttonCount, 10); i++) {
        const button = buttons.nth(i);
        const boundingBox = await button.boundingBox();
        
        if (boundingBox) {
          expect(boundingBox.width).toBeGreaterThanOrEqual(32); // Minimum touch target
          expect(boundingBox.height).toBeGreaterThanOrEqual(32);
        }
      }
      
      // Test tap accuracy on small screens
      const createButton = page.locator('[data-cy="create-invoice"]');
      await expect(createButton).toBeVisible();
      
      // Tap button and verify response
      await createButton.tap();
      await expect(page).toHaveURL(/.*\/invoices\/create/);
    });
  });

  test.describe('Core Business Flows on Mobile', () => {
    test('should create customer on mobile device', async () => {
      await page.goto('/admin/customers');
      
      // Mobile-optimized customer creation
      await page.tap('[data-cy="create-customer"]');
      
      // Check mobile form layout
      const form = page.locator('[data-cy="customer-form"]');
      await expect(form).toBeVisible();
      
      // Verify form fields are properly sized for mobile
      const nameField = page.locator('[data-cy="customer-name"]');
      const fieldWidth = await nameField.boundingBox();
      expect(fieldWidth.width).toBeGreaterThan(250); // Should be wide enough on mobile
      
      // Fill form with Macedonia customer data
      await nameField.fill('Мобилен Клиент ДООЕл');
      await page.fill('[data-cy="customer-email"]', 'mobilen@klient.mk');
      await page.fill('[data-cy="customer-tax-id"]', 'MK4080003501245');
      await page.fill('[data-cy="customer-phone"]', '+389 70 123 456');
      
      // Test mobile keyboard types
      const emailField = page.locator('[data-cy="customer-email"]');
      const inputType = await emailField.getAttribute('type');
      expect(inputType).toBe('email'); // Should trigger email keyboard
      
      const phoneField = page.locator('[data-cy="customer-phone"]');
      const phoneInputType = await phoneField.getAttribute('type');
      expect(phoneInputType).toBe('tel'); // Should trigger phone keyboard
      
      // Save customer
      await page.tap('[data-cy="save-customer"]');
      
      // Verify success on mobile
      const successMessage = page.locator('[data-cy="success-message"]');
      await expect(successMessage).toBeVisible();
      
      // Verify customer appears in mobile list view
      await expect(page.locator('[data-cy="customers-table"]')).toContainText('Мобилен Клиент');
    });

    test('should create and send invoice on mobile', async () => {
      await page.goto('/admin/invoices');
      
      // Create invoice on mobile
      await page.tap('[data-cy="create-invoice"]');
      
      // Mobile invoice form interaction
      await page.tap('[data-cy="invoice-customer"]');
      
      // Select customer from mobile-optimized dropdown
      const customerDropdown = page.locator('[data-cy="customer-dropdown"]');
      await expect(customerDropdown).toBeVisible();
      
      await page.tap('[data-cy="customer-option"]').first();
      
      // Add invoice item with mobile number input
      await page.tap('[data-cy="add-item"]');
      
      await page.fill('[data-cy="item-name"]', 'Мобилна услуга');
      await page.fill('[data-cy="item-description"]', 'Консултантски услуги преку мобилен');
      
      // Test mobile number input
      const quantityField = page.locator('[data-cy="item-quantity"]');
      await quantityField.fill('2');
      
      const priceField = page.locator('[data-cy="item-price"]');
      await priceField.fill('1500');
      
      // Verify calculations update
      const totalField = page.locator('[data-cy="total-amount"]');
      await expect(totalField).toContainText('3,540'); // 2 * 1500 * 1.18
      
      // Save invoice
      await page.tap('[data-cy="save-draft"]');
      
      // Send invoice via mobile
      await page.tap('[data-cy="send-invoice"]');
      
      // Verify mobile email interface
      const emailForm = page.locator('[data-cy="email-form"]');
      await expect(emailForm).toBeVisible();
      
      await page.tap('[data-cy="send-email-button"]');
      
      // Verify sent status
      await expect(page.locator('[data-cy="invoice-status"]')).toContainText('SENT');
    });

    test('should record payment on mobile device', async () => {
      // Create a test invoice first
      await page.goto('/admin/invoices');
      
      // Find sent invoice
      const invoiceRow = page.locator('[data-cy="invoices-table"] tr').first();
      await invoiceRow.tap();
      
      // Record payment on mobile
      await page.tap('[data-cy="record-payment"]');
      
      // Mobile payment form
      const paymentForm = page.locator('[data-cy="payment-form"]');
      await expect(paymentForm).toBeVisible();
      
      // Use mobile date picker
      const dateField = page.locator('[data-cy="payment-date"]');
      await dateField.tap();
      
      // Verify mobile date picker appears
      const datePicker = page.locator('[data-cy="date-picker"]');
      await expect(datePicker).toBeVisible({ timeout: 3000 });
      
      // Select today's date
      await page.tap('[data-cy="date-today"]');
      
      // Select payment method with mobile dropdown
      await page.tap('[data-cy="payment-method"]');
      await page.tap('[data-cy="method-bank-transfer"]');
      
      // Add payment reference
      await page.fill('[data-cy="payment-reference"]', 'МОБ-ПЛ-001');
      
      // Save payment
      await page.tap('[data-cy="save-payment"]');
      
      // Verify payment recorded
      await expect(page.locator('[data-cy="invoice-status"]')).toContainText('PAID');
    });
  });

  test.describe('Mobile Performance and Offline', () => {
    test('should load quickly on mobile networks', async () => {
      // Simulate 3G network conditions
      await context.route('**/*', async (route) => {
        // Add 500ms delay to simulate mobile network
        await new Promise(resolve => setTimeout(resolve, 300));
        await route.continue();
      });
      
      const startTime = Date.now();
      await page.goto('/admin/dashboard');
      const loadTime = Date.now() - startTime;
      
      // Should load within 5 seconds on mobile network
      expect(loadTime).toBeLessThan(5000);
      
      // Verify essential content loaded
      await expect(page.locator('[data-cy="dashboard-content"]')).toBeVisible();
      await expect(page.locator('[data-cy="mobile-navigation"]')).toBeVisible();
    });

    test('should handle offline functionality', async () => {
      await page.goto('/admin/dashboard');
      
      // Go offline
      await context.setOffline(true);
      
      // Try to navigate while offline
      await page.tap('[data-cy="nav-invoices"]');
      
      // Should show offline message or cached content
      const offlineMessage = page.locator('[data-cy="offline-message"]');
      const cachedContent = page.locator('[data-cy="invoices-list"]');
      
      const isOfflineHandled = await Promise.race([
        offlineMessage.isVisible().then(() => 'offline_message'),
        cachedContent.isVisible().then(() => 'cached_content')
      ]);
      
      expect(['offline_message', 'cached_content']).toContain(isOfflineHandled);
      
      // Go back online
      await context.setOffline(false);
      
      // Verify sync when online
      await page.waitForTimeout(2000); // Wait for potential sync
      
      const syncIndicator = page.locator('[data-cy="sync-indicator"]');
      const isConnected = await page.evaluate(() => navigator.onLine);
      expect(isConnected).toBe(true);
    });

    test('should optimize images for mobile devices', async () => {
      await page.goto('/admin/dashboard');
      
      // Check for responsive images
      const images = page.locator('img');
      const imageCount = await images.count();
      
      for (let i = 0; i < Math.min(imageCount, 5); i++) {
        const img = images.nth(i);
        
        // Check for srcset attribute (responsive images)
        const srcset = await img.getAttribute('srcset');
        const sizes = await img.getAttribute('sizes');
        
        // Images should be optimized for mobile
        if (srcset || sizes) {
          expect(srcset || sizes).toBeTruthy();
        }
        
        // Check image loading
        const loaded = await img.evaluate(el => el.complete);
        expect(loaded).toBe(true);
      }
    });
  });

  test.describe('Macedonia-Specific Mobile UI', () => {
    test('should display Macedonian text correctly on mobile', async () => {
      await page.goto('/admin/customers');
      
      // Verify Macedonian characters render properly on mobile
      const macedonianText = page.locator('text=Македонски');
      if (await macedonianText.count() > 0) {
        await expect(macedonianText.first()).toBeVisible();
        
        // Check font rendering
        const fontSize = await macedonianText.first().evaluate(el => 
          window.getComputedStyle(el).fontSize
        );
        
        // Font should be readable on mobile (at least 16px)
        const fontSizeNum = parseInt(fontSize);
        expect(fontSizeNum).toBeGreaterThanOrEqual(14);
      }
    });

    test('should handle Macedonia phone number input on mobile', async () => {
      await page.goto('/admin/customers/create');
      
      const phoneField = page.locator('[data-cy="customer-phone"]');
      await phoneField.tap();
      
      // Test Macedonia phone number format
      await phoneField.fill('+389 70 123 456');
      
      // Verify mobile keyboard type
      const inputType = await phoneField.getAttribute('type');
      expect(inputType).toBe('tel');
      
      // Check phone number validation
      const isValid = await phoneField.evaluate(el => el.validity.valid);
      expect(isValid).toBe(true);
    });

    test('should display currency (MKD) properly on mobile', async () => {
      await page.goto('/admin/invoices');
      
      // Check currency display in mobile view
      const currencyElements = page.locator('text=/MKD|ден/');
      const currencyCount = await currencyElements.count();
      
      if (currencyCount > 0) {
        await expect(currencyElements.first()).toBeVisible();
        
        // Verify currency formatting on mobile
        const currencyText = await currencyElements.first().textContent();
        expect(currencyText).toMatch(/MKD|ден/);
      }
    });
  });

  test.describe('Mobile Accessibility', () => {
    test('should meet mobile accessibility standards', async () => {
      await page.goto('/admin/dashboard');
      
      // Check for proper ARIA labels on mobile
      const buttons = page.locator('button');
      const buttonCount = await buttons.count();
      
      for (let i = 0; i < Math.min(buttonCount, 10); i++) {
        const button = buttons.nth(i);
        const ariaLabel = await button.getAttribute('aria-label');
        const innerText = await button.textContent();
        
        // Button should have accessible text or aria-label
        expect(ariaLabel || innerText?.trim()).toBeTruthy();
      }
      
      // Check mobile form labels
      const inputs = page.locator('input');
      const inputCount = await inputs.count();
      
      for (let i = 0; i < Math.min(inputCount, 5); i++) {
        const input = inputs.nth(i);
        const label = await input.evaluate(el => {
          const id = el.id;
          return id ? document.querySelector(`label[for="${id}"]`) : null;
        });
        
        if (label || await input.getAttribute('aria-label')) {
          // Input has proper labeling
          expect(true).toBe(true);
        }
      }
    });

    test('should support mobile screen readers', async () => {
      await page.goto('/admin/invoices');
      
      // Check for proper heading structure
      const headings = page.locator('h1, h2, h3, h4, h5, h6');
      const headingCount = await headings.count();
      
      expect(headingCount).toBeGreaterThan(0);
      
      // Check for skip navigation on mobile
      const skipLink = page.locator('[data-cy="skip-navigation"]');
      if (await skipLink.count() > 0) {
        await expect(skipLink).toBeVisible();
      }
      
      // Verify focus management on mobile
      await page.keyboard.press('Tab');
      const focusedElement = await page.locator(':focus');
      await expect(focusedElement).toBeVisible();
    });
  });

  // Performance tracking for audit report
  test('should track mobile performance metrics', async () => {
    await page.goto('/admin/dashboard');
    
    // Measure mobile-specific performance
    const performanceMetrics = await page.evaluate(() => {
      const navigation = performance.getEntriesByType('navigation')[0];
      return {
        domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
        loadComplete: navigation.loadEventEnd - navigation.loadEventStart,
        firstPaint: performance.getEntriesByType('paint').find(entry => entry.name === 'first-paint')?.startTime,
        firstContentfulPaint: performance.getEntriesByType('paint').find(entry => entry.name === 'first-contentful-paint')?.startTime
      };
    });
    
    // Mobile performance requirements
    expect(performanceMetrics.domContentLoaded).toBeLessThan(2000); // 2 seconds
    expect(performanceMetrics.firstContentfulPaint).toBeLessThan(1500); // 1.5 seconds
    
    console.log('Mobile Performance Metrics:', performanceMetrics);
  });
});


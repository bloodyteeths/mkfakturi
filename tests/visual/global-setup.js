// AUD-01: Global Setup for Visual Testing
// Prepares test environment and creates test data

import { chromium } from '@playwright/test';

async function globalSetup(config) {
  console.log('🔧 Setting up visual testing environment...');
  
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // Wait for application to be ready
    await page.goto('http://localhost:8000/api/health', { waitUntil: 'networkidle' });
    
    // Login as admin to create test data
    await page.goto('http://localhost:8000/admin/auth/login');
    await page.fill('input[name="email"]', 'admin@invoiceshelf.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard');

    console.log('✅ Visual testing environment ready');
  } catch (error) {
    console.error('❌ Failed to setup visual testing environment:', error);
    throw error;
  } finally {
    await browser.close();
  }
}

export default globalSetup;


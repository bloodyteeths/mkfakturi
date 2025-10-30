#!/usr/bin/env node

/**
 * OnivoFetcher - Playwright automation for Onivo accounting system data extraction
 * 
 * Automates login and data export from Onivo accounting software for Macedonia market
 * Supports CSV/Excel exports of customers, invoices, items, and payments
 * 
 * Usage:
 *   node index.js                    # Run with environment config
 *   HEADLESS=false node index.js     # Run in headed mode for debugging
 *   DEBUG=true node index.js         # Enable verbose logging
 * 
 * Environment Variables:
 *   ONIVO_EMAIL       - Login email for Onivo account
 *   ONIVO_PASS        - Login password for Onivo account  
 *   ONIVO_URL         - Onivo instance URL (default: https://demo.onivo.mk)
 *   HEADLESS          - Run in headless mode (default: true)
 *   DEBUG             - Enable debug logging (default: false)
 *   DOWNLOAD_PATH     - Download directory (default: ./downloads)
 * 
 */

const { chromium } = require('@playwright/test');
const fs = require('fs-extra');
const path = require('path');
const winston = require('winston');
require('dotenv').config();

// Configuration
const CONFIG = {
  url: process.env.ONIVO_URL || 'https://demo.onivo.mk',
  email: process.env.ONIVO_EMAIL || '',
  password: process.env.ONIVO_PASS || '',
  headless: process.env.HEADLESS !== 'false',
  debug: process.env.DEBUG === 'true',
  downloadPath: process.env.DOWNLOAD_PATH || './downloads',
  timeout: 30000,
  retries: 3,
  userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
};

// Logger setup
const logger = winston.createLogger({
  level: CONFIG.debug ? 'debug' : 'info',
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.printf(({ timestamp, level, message, stack }) => {
      return `${timestamp} [${level.toUpperCase()}]: ${message}${stack ? '\n' + stack : ''}`;
    })
  ),
  transports: [
    new winston.transports.Console(),
    new winston.transports.File({ filename: 'onivo-fetcher.log' })
  ]
});

// Export types configuration for Macedonian accounting software
const EXPORT_TYPES = {
  customers: {
    name: 'Кліенти', // Macedonian: Clients
    path: '/customers/export',
    filename: 'klienti',
    selectors: {
      exportButton: '[data-export="customers"]',
      formatSelect: 'select[name="format"]',
      downloadButton: '.btn-download'
    }
  },
  invoices: {
    name: 'Фактури', // Macedonian: Invoices
    path: '/invoices/export', 
    filename: 'fakturi',
    selectors: {
      exportButton: '[data-export="invoices"]',
      formatSelect: 'select[name="format"]',
      downloadButton: '.btn-download'
    }
  },
  items: {
    name: 'Ставки', // Macedonian: Items
    path: '/items/export',
    filename: 'stavki', 
    selectors: {
      exportButton: '[data-export="items"]',
      formatSelect: 'select[name="format"]',
      downloadButton: '.btn-download'
    }
  },
  payments: {
    name: 'Плаќања', // Macedonian: Payments
    path: '/payments/export',
    filename: 'plateni',
    selectors: {
      exportButton: '[data-export="payments"]',
      formatSelect: 'select[name="format"]',
      downloadButton: '.btn-download'
    }
  }
};

/**
 * Validates required configuration
 */
function validateConfig() {
  logger.info('Validating configuration...');
  
  if (!CONFIG.email || !CONFIG.password) {
    logger.error('Missing required environment variables: ONIVO_EMAIL and ONIVO_PASS');
    process.exit(1);
  }
  
  // Ensure download directory exists
  if (!fs.existsSync(CONFIG.downloadPath)) {
    logger.info(`Creating download directory: ${CONFIG.downloadPath}`);
    fs.ensureDirSync(CONFIG.downloadPath);
  }
  
  logger.info('Configuration validated successfully');
}

/**
 * Creates browser instance with Macedonia-specific settings
 */
async function createBrowser() {
  logger.info('Launching browser...');
  
  const browser = await chromium.launch({
    headless: CONFIG.headless,
    args: [
      '--no-sandbox',
      '--disable-blink-features=AutomationControlled',
      '--disable-features=VizDisplayCompositor'
    ]
  });
  
  const context = await browser.newContext({
    userAgent: CONFIG.userAgent,
    viewport: { width: 1366, height: 768 },
    locale: 'mk-MK', // Macedonian locale
    timezoneId: 'Europe/Skopje'
  });
  
  // Configure downloads
  const page = await context.newPage();
  
  // Set download behavior
  await page._client.send('Page.setDownloadBehavior', {
    behavior: 'allow',
    downloadPath: path.resolve(CONFIG.downloadPath)
  });
  
  logger.info('Browser launched successfully');
  return { browser, context, page };
}

/**
 * Performs login to Onivo system
 */
async function login(page) {
  logger.info('Attempting login to Onivo...');
  
  try {
    // Navigate to login page
    await page.goto(`${CONFIG.url}/login`, { 
      waitUntil: 'networkidle',
      timeout: CONFIG.timeout 
    });
    
    // Wait for login form
    await page.waitForSelector('input[name="email"], input[type="email"]', { timeout: 10000 });
    
    // Fill login credentials
    await page.fill('input[name="email"], input[type="email"]', CONFIG.email);
    await page.fill('input[name="password"], input[type="password"]', CONFIG.password);
    
    logger.debug('Credentials filled, submitting login form...');
    
    // Submit login form
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"], .btn-login, input[type="submit"]')
    ]);
    
    // Verify successful login by checking for dashboard elements
    const isDashboard = await page.locator('.dashboard, .home, .main-content').first().isVisible({ timeout: 5000 });
    
    if (!isDashboard) {
      throw new Error('Login failed - dashboard not detected');
    }
    
    logger.info('Login successful');
    return true;
    
  } catch (error) {
    logger.error('Login failed:', error.message);
    
    // Capture screenshot for debugging
    if (CONFIG.debug) {
      await page.screenshot({ path: 'login-error.png' });
      logger.debug('Login error screenshot saved as login-error.png');
    }
    
    throw error;
  }
}

/**
 * Exports data for a specific type (customers, invoices, etc.)
 */
async function exportData(page, exportType) {
  const config = EXPORT_TYPES[exportType];
  if (!config) {
    throw new Error(`Unknown export type: ${exportType}`);
  }
  
  logger.info(`Starting export for ${config.name} (${exportType})...`);
  
  try {
    // Navigate to the specific export section
    const exportUrl = `${CONFIG.url}${config.path}`;
    await page.goto(exportUrl, { 
      waitUntil: 'networkidle',
      timeout: CONFIG.timeout 
    });
    
    // Alternative navigation approaches for different Onivo versions
    const navigationAttempts = [
      // Attempt 1: Direct export button
      async () => {
        const exportBtn = page.locator(config.selectors.exportButton);
        if (await exportBtn.isVisible({ timeout: 2000 })) {
          await exportBtn.click();
          return true;
        }
        return false;
      },
      
      // Attempt 2: Menu navigation
      async () => {
        const menuItems = page.locator('a, .menu-item, .nav-link');
        const exportLink = menuItems.filter({ hasText: config.name });
        if (await exportLink.first().isVisible({ timeout: 2000 })) {
          await exportLink.first().click();
          await page.waitForLoadState('networkidle');
          return true;
        }
        return false;
      },
      
      // Attempt 3: Generic export functionality
      async () => {
        const exportButton = page.locator('button, a').filter({ hasText: /извези|export|експорт/i });
        if (await exportButton.first().isVisible({ timeout: 2000 })) {
          await exportButton.first().click();
          return true;
        }
        return false;
      }
    ];
    
    let navigationSuccess = false;
    for (const attempt of navigationAttempts) {
      try {
        if (await attempt()) {
          navigationSuccess = true;
          break;
        }
      } catch (err) {
        logger.debug(`Navigation attempt failed: ${err.message}`);
      }
    }
    
    if (!navigationSuccess) {
      logger.warn(`Could not find export option for ${config.name}, trying direct download...`);
    }
    
    // Configure export format (prefer CSV, fallback to Excel)
    const formatSelector = page.locator(config.selectors.formatSelect);
    if (await formatSelector.isVisible({ timeout: 2000 })) {
      await formatSelector.selectOption('csv').catch(() => 
        formatSelector.selectOption('excel').catch(() => 
          logger.debug('Format selection not available')
        )
      );
    }
    
    // Set up download promise before clicking download
    const downloadPromise = page.waitForEvent('download', { timeout: 30000 });
    
    // Trigger download
    const downloadTriggers = [
      config.selectors.downloadButton,
      '.btn-download',
      'button[type="submit"]',
      '.export-btn',
      'a[href*="export"]'
    ];
    
    let downloadTriggered = false;
    for (const selector of downloadTriggers) {
      try {
        const element = page.locator(selector);
        if (await element.isVisible({ timeout: 1000 })) {
          await element.click();
          downloadTriggered = true;
          break;
        }
      } catch (err) {
        logger.debug(`Download trigger "${selector}" not found`);
      }
    }
    
    if (!downloadTriggered) {
      throw new Error(`Could not trigger download for ${config.name}`);
    }
    
    logger.info(`Download triggered for ${config.name}, waiting for file...`);
    
    // Wait for download and save with meaningful name
    const download = await downloadPromise;
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `${config.filename}_${timestamp}.${download.suggestedFilename().split('.').pop() || 'csv'}`;
    const filepath = path.join(CONFIG.downloadPath, filename);
    
    await download.saveAs(filepath);
    
    logger.info(`Successfully exported ${config.name} to: ${filepath}`);
    return filepath;
    
  } catch (error) {
    logger.error(`Export failed for ${config.name}:`, error.message);
    
    if (CONFIG.debug) {
      await page.screenshot({ path: `export-error-${exportType}.png` });
      logger.debug(`Export error screenshot saved as export-error-${exportType}.png`);
    }
    
    throw error;
  }
}

/**
 * Performs data extraction with retry logic
 */
async function performExtraction(exportTypes = Object.keys(EXPORT_TYPES)) {
  let browser, context, page;
  const results = {
    success: [],
    failed: [],
    timestamp: new Date().toISOString()
  };
  
  try {
    // Create browser instance
    ({ browser, context, page } = await createBrowser());
    
    // Login to Onivo
    await login(page);
    
    // Process each export type
    for (const exportType of exportTypes) {
      logger.info(`Processing export type: ${exportType}`);
      
      let attempts = 0;
      let success = false;
      
      while (attempts < CONFIG.retries && !success) {
        attempts++;
        
        try {
          const filepath = await exportData(page, exportType);
          results.success.push({
            type: exportType,
            name: EXPORT_TYPES[exportType].name,
            filepath: filepath,
            size: fs.statSync(filepath).size
          });
          success = true;
          
        } catch (error) {
          logger.warn(`Attempt ${attempts}/${CONFIG.retries} failed for ${exportType}: ${error.message}`);
          
          if (attempts < CONFIG.retries) {
            logger.info(`Retrying in 2 seconds...`);
            await page.waitForTimeout(2000);
          } else {
            results.failed.push({
              type: exportType,
              name: EXPORT_TYPES[exportType].name,
              error: error.message
            });
          }
        }
      }
    }
    
  } catch (error) {
    logger.error('Critical error during extraction:', error);
    throw error;
    
  } finally {
    // Cleanup
    if (page) await page.close().catch(() => {});
    if (context) await context.close().catch(() => {});
    if (browser) await browser.close().catch(() => {});
  }
  
  return results;
}

/**
 * Generates summary report
 */
function generateReport(results) {
  logger.info('='.repeat(60));
  logger.info('ONIVO EXTRACTION SUMMARY');
  logger.info('='.repeat(60));
  logger.info(`Timestamp: ${results.timestamp}`);
  logger.info(`Successful exports: ${results.success.length}`);
  logger.info(`Failed exports: ${results.failed.length}`);
  logger.info('');
  
  if (results.success.length > 0) {
    logger.info('SUCCESSFUL EXPORTS:');
    results.success.forEach(item => {
      const sizeKB = Math.round(item.size / 1024);
      logger.info(`✓ ${item.name} (${item.type}) - ${sizeKB}KB - ${item.filepath}`);
    });
    logger.info('');
  }
  
  if (results.failed.length > 0) {
    logger.info('FAILED EXPORTS:');
    results.failed.forEach(item => {
      logger.info(`✗ ${item.name} (${item.type}) - ${item.error}`);
    });
    logger.info('');
  }
  
  // Save detailed report
  const reportPath = path.join(CONFIG.downloadPath, `extraction-report-${Date.now()}.json`);
  fs.writeJsonSync(reportPath, results, { spaces: 2 });
  logger.info(`Detailed report saved: ${reportPath}`);
  
  logger.info('='.repeat(60));
  
  return results.failed.length === 0;
}

/**
 * Main execution function
 */
async function main() {
  try {
    logger.info('OnivoFetcher starting...');
    
    // Validate configuration
    validateConfig();
    
    // Parse command line arguments
    const args = process.argv.slice(2);
    const testMode = args.includes('--test');
    const exportTypes = args.filter(arg => EXPORT_TYPES[arg]).length > 0 
      ? args.filter(arg => EXPORT_TYPES[arg]) 
      : Object.keys(EXPORT_TYPES);
    
    if (testMode) {
      logger.info('Running in test mode - will only test login');
      const { browser, context, page } = await createBrowser();
      
      try {
        await login(page);
        logger.info('✓ Test successful - login works');
        return true;
      } finally {
        await page.close().catch(() => {});
        await context.close().catch(() => {});
        await browser.close().catch(() => {});
      }
    }
    
    logger.info(`Starting extraction for: ${exportTypes.join(', ')}`);
    
    // Perform extraction
    const results = await performExtraction(exportTypes);
    
    // Generate report
    const success = generateReport(results);
    
    if (success) {
      logger.info('✓ All exports completed successfully');
      process.exit(0);
    } else {
      logger.error('✗ Some exports failed - check the report for details');
      process.exit(1);
    }
    
  } catch (error) {
    logger.error('Fatal error:', error);
    process.exit(1);
  }
}

// Handle process signals gracefully
process.on('SIGINT', () => {
  logger.info('Received SIGINT, shutting down gracefully...');
  process.exit(0);
});

process.on('SIGTERM', () => {
  logger.info('Received SIGTERM, shutting down gracefully...');
  process.exit(0);
});

// Run if called directly
if (require.main === module) {
  main();
}

module.exports = {
  main,
  performExtraction,
  validateConfig,
  CONFIG,
  EXPORT_TYPES
};
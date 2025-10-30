#!/usr/bin/env node

/**
 * Test script for OnivoFetcher validation
 * 
 * Tests configuration, dependencies, and basic functionality
 * without requiring actual Onivo credentials
 */

const fs = require('fs');
const path = require('path');

// ANSI color codes for console output
const colors = {
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  reset: '\x1b[0m',
  bold: '\x1b[1m'
};

function log(message, color = '') {
  console.log(`${color}${message}${colors.reset}`);
}

function success(message) {
  log(`✓ ${message}`, colors.green);
}

function error(message) {
  log(`✗ ${message}`, colors.red);
}

function warning(message) {
  log(`⚠ ${message}`, colors.yellow);
}

function info(message) {
  log(`ℹ ${message}`, colors.blue);
}

async function testDependencies() {
  info('Testing dependencies...');
  
  try {
    // Test Node.js version
    const nodeVersion = process.version;
    const majorVersion = parseInt(nodeVersion.slice(1).split('.')[0]);
    
    if (majorVersion >= 16) {
      success(`Node.js version: ${nodeVersion}`);
    } else {
      error(`Node.js version ${nodeVersion} is too old. Requires >= 16.0.0`);
      return false;
    }
    
    // Test required dependencies
    const requiredDeps = ['@playwright/test', 'dotenv', 'fs-extra', 'winston'];
    
    for (const dep of requiredDeps) {
      try {
        require.resolve(dep);
        success(`Dependency found: ${dep}`);
      } catch (err) {
        error(`Missing dependency: ${dep}`);
        warning(`Run: npm install ${dep}`);
        return false;
      }
    }
    
    return true;
    
  } catch (err) {
    error(`Dependency test failed: ${err.message}`);
    return false;
  }
}

async function testConfiguration() {
  info('Testing configuration...');
  
  try {
    // Load the main module
    const onivoFetcher = require('./index.js');
    
    // Test configuration validation
    const originalEnv = { ...process.env };
    
    // Test with missing credentials
    delete process.env.ONIVO_EMAIL;
    delete process.env.ONIVO_PASS;
    
    try {
      onivoFetcher.validateConfig();
      error('Configuration validation should fail with missing credentials');
      return false;
    } catch (err) {
      success('Configuration validation correctly rejects missing credentials');
    }
    
    // Test with valid configuration
    process.env.ONIVO_EMAIL = 'test@example.com';
    process.env.ONIVO_PASS = 'testpass';
    
    try {
      onivoFetcher.validateConfig();
      success('Configuration validation passes with credentials');
    } catch (err) {
      error(`Configuration validation failed: ${err.message}`);
      return false;
    }
    
    // Restore original environment
    process.env = originalEnv;
    
    return true;
    
  } catch (err) {
    error(`Configuration test failed: ${err.message}`);
    return false;
  }
}

async function testFileStructure() {
  info('Testing file structure...');
  
  const requiredFiles = [
    'package.json',
    'index.js',
    'README.md',
    '.env.example',
    '.gitignore'
  ];
  
  for (const file of requiredFiles) {
    if (fs.existsSync(path.join(__dirname, file))) {
      success(`File exists: ${file}`);
    } else {
      error(`Missing file: ${file}`);
      return false;
    }
  }
  
  // Test package.json structure
  try {
    const packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
    
    if (packageJson.name === 'onivo-fetcher') {
      success('Package name is correct');
    } else {
      error(`Package name should be 'onivo-fetcher', got '${packageJson.name}'`);
    }
    
    if (packageJson.dependencies && packageJson.dependencies['@playwright/test']) {
      success('Playwright dependency declared');
    } else {
      error('Playwright dependency missing in package.json');
    }
    
  } catch (err) {
    error(`Package.json validation failed: ${err.message}`);
    return false;
  }
  
  return true;
}

async function testExportTypes() {
  info('Testing export type configuration...');
  
  try {
    const onivoFetcher = require('./index.js');
    const exportTypes = onivoFetcher.EXPORT_TYPES;
    
    const expectedTypes = ['customers', 'invoices', 'items', 'payments'];
    
    for (const type of expectedTypes) {
      if (exportTypes[type]) {
        success(`Export type configured: ${type} (${exportTypes[type].name})`);
        
        // Validate structure
        const config = exportTypes[type];
        if (config.name && config.path && config.filename && config.selectors) {
          success(`  Valid structure for ${type}`);
        } else {
          error(`  Invalid structure for ${type}`);
          return false;
        }
      } else {
        error(`Missing export type: ${type}`);
        return false;
      }
    }
    
    return true;
    
  } catch (err) {
    error(`Export types test failed: ${err.message}`);
    return false;
  }
}

async function testPlaywrightSetup() {
  info('Testing Playwright setup...');
  
  try {
    const { chromium } = require('@playwright/test');
    
    // Test browser launch (don't actually launch, just validate import)
    if (typeof chromium.launch === 'function') {
      success('Playwright chromium driver is available');
    } else {
      error('Playwright chromium driver is not properly installed');
      warning('Run: npx playwright install');
      return false;
    }
    
    return true;
    
  } catch (err) {
    error(`Playwright test failed: ${err.message}`);
    warning('Run: npm install @playwright/test && npx playwright install');
    return false;
  }
}

async function runAllTests() {
  log(`${colors.bold}${colors.blue}OnivoFetcher Test Suite${colors.reset}`);
  log('='.repeat(50));
  
  const tests = [
    { name: 'Dependencies', fn: testDependencies },
    { name: 'File Structure', fn: testFileStructure },
    { name: 'Configuration', fn: testConfiguration },
    { name: 'Export Types', fn: testExportTypes },
    { name: 'Playwright Setup', fn: testPlaywrightSetup }
  ];
  
  let passed = 0;
  let failed = 0;
  
  for (const test of tests) {
    log(`\n${colors.bold}Testing: ${test.name}${colors.reset}`);
    
    try {
      const result = await test.fn();
      if (result) {
        passed++;
        success(`${test.name} test passed`);
      } else {
        failed++;
        error(`${test.name} test failed`);
      }
    } catch (err) {
      failed++;
      error(`${test.name} test crashed: ${err.message}`);
    }
  }
  
  log('\n' + '='.repeat(50));
  log(`${colors.bold}Test Results:${colors.reset}`);
  log(`${colors.green}Passed: ${passed}${colors.reset}`);
  log(`${colors.red}Failed: ${failed}${colors.reset}`);
  
  if (failed === 0) {
    log(`\n${colors.green}${colors.bold}✓ All tests passed! OnivoFetcher is ready to use.${colors.reset}`);
    log(`\nNext steps:`);
    log(`1. Copy .env.example to .env`);
    log(`2. Add your Onivo credentials to .env`);
    log(`3. Run: npm run fetch`);
    return true;
  } else {
    log(`\n${colors.red}${colors.bold}✗ Some tests failed. Please fix the issues above.${colors.reset}`);
    return false;
  }
}

// Run tests if called directly
if (require.main === module) {
  runAllTests()
    .then(success => process.exit(success ? 0 : 1))
    .catch(err => {
      error(`Test suite crashed: ${err.message}`);
      process.exit(1);
    });
}

module.exports = {
  runAllTests,
  testDependencies,
  testConfiguration,
  testFileStructure,
  testExportTypes,
  testPlaywrightSetup
};
// AUD-01: Playwright Visual Regression Testing Configuration
// 
// This configuration implements visual baseline testing as required by
// ROADMAP-FINAL.md Section B - AUD-01.
//
// Visual regression testing ensures:
// - UI consistency across releases
// - Browser compatibility validation
// - Mobile responsive design verification
// - Macedonia-specific UI elements preservation
// - Partner console visual integrity
// - Invoice/PDF template consistency
//
// Usage:
//   npx playwright test --project=chromium
//   npx playwright test --update-snapshots (to update baselines)
//   npx playwright show-report (to view results)

import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/visual',
  
  /* Run tests in files in parallel */
  fullyParallel: true,
  
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : undefined,
  
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['html', { open: 'never' }],
    ['json', { outputFile: 'test-results/visual-test-results.json' }],
    ['junit', { outputFile: 'test-results/visual-results.xml' }]
  ],
  
  /* Shared settings for all the projects below. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: 'http://localhost:8000',
    
    /* Collect trace when retrying the failed test. */
    trace: 'on-first-retry',
    
    /* Take screenshot on failure */
    screenshot: 'only-on-failure',
    
    /* Record video on failure */
    video: 'retain-on-failure',
    
    /* Global timeout for each action */
    actionTimeout: 30000,
    
    /* Global timeout for navigation */
    navigationTimeout: 60000,
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'chromium',
      use: { 
        ...devices['Desktop Chrome'],
        viewport: { width: 1280, height: 720 }
      },
    },

    {
      name: 'firefox',
      use: { 
        ...devices['Desktop Firefox'],
        viewport: { width: 1280, height: 720 }
      },
    },

    {
      name: 'webkit',
      use: { 
        ...devices['Desktop Safari'],
        viewport: { width: 1280, height: 720 }
      },
    },

    /* Test against mobile viewports. */
    {
      name: 'Mobile Chrome',
      use: { 
        ...devices['Pixel 5']
      },
    },
    {
      name: 'Mobile Safari',
      use: { 
        ...devices['iPhone 12']
      },
    },

    /* Test against branded browsers. */
    // {
    //   name: 'Microsoft Edge',
    //   use: { ...devices['Desktop Edge'], channel: 'msedge' },
    // },
    // {
    //   name: 'Google Chrome',
    //   use: { ...devices['Desktop Chrome'], channel: 'chrome' },
    // },
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'php artisan serve --port=8000',
  //   port: 8000,
  //   reuseExistingServer: !process.env.CI,
  //   timeout: 60000,
  // },

  /* Global test configuration */
  // globalSetup: './tests/visual/global-setup.js',
  // globalTeardown: './tests/visual/global-teardown.js',

  /* Expect configuration for visual testing */
  expect: {
    /* Configure image comparison */
    toHaveScreenshot: {
      /* Threshold for pixel difference */
      threshold: 0.2,
      /* Max allowed pixel difference */
      maxDiffPixels: 1000,
      /* Animation handling */
      animations: 'disabled',
    },
    toMatchSnapshot: {
      threshold: 0.2,
      maxDiffPixels: 1000,
    }
  }
});


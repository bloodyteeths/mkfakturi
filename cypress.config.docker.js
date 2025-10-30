const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://app:80',
    viewportWidth: 1280,
    viewportHeight: 720,
    video: true,
    screenshotOnRunFailure: true,
    defaultCommandTimeout: 15000,
    requestTimeout: 20000,
    responseTimeout: 20000,
    failOnStatusCode: false,
    env: {
      // Test user credentials
      ADMIN_EMAIL: 'admin@invoiceshelf.com',
      ADMIN_PASSWORD: 'password',
      PARTNER_EMAIL: 'partner@accounting.mk',
      PARTNER_PASSWORD: 'password',
      // Test company data
      TEST_COMPANY_NAME: 'Test Company Macedonia',
      TEST_CUSTOMER_NAME: 'Test Customer',
    },
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'cypress/support/e2e.js',
  },
})
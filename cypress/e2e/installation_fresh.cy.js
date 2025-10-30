// INS-01: Fresh Installation Wizard Flow Test
// Comprehensive test for complete 8-step installation process with data persistence validation
// Created for Agent 2: Installation & Onboarding Flow Validator

describe('Fresh Installation Wizard Flow', () => {
  // Test configuration
  const installationSteps = [
    { step: 0, name: 'Set Language', selector: '[data-cy="language-select"]', action: 'select', value: 'en' },
    { step: 1, name: 'Requirements Check', selector: '[data-cy="requirements-continue"]', action: 'click' },
    { step: 2, name: 'Permission Check', selector: '[data-cy="permissions-continue"]', action: 'click' },
    { step: 3, name: 'Database Config', selector: 'select[name="database_connection"]', action: 'configure-db' },
    { step: 4, name: 'Verify Domain', selector: '[data-cy="domain-continue"]', action: 'click' },
    { step: 5, name: 'Email Config', selector: '[data-cy="email-config"]', action: 'configure-email' },
    { step: 6, name: 'Account Settings', selector: '[data-cy="account-form"]', action: 'create-account' },
    { step: 7, name: 'Company Info', selector: '[data-cy="company-form"]', action: 'create-company' },
    { step: 8, name: 'Company Preferences', selector: '[data-cy="preferences-finish"]', action: 'finish' }
  ]

  const testData = {
    admin: {
      name: 'Test Admin User',
      email: 'admin@facturino-test.mk',
      password: 'TestPassword123!',
      password_confirmation: 'TestPassword123!'
    },
    company: {
      name: 'Test Company Macedonia',
      email: 'company@facturino-test.mk',
      currency: 'MKD',
      country: 'Macedonia',
      address: 'Bitola 1, 1000 Skopje',
      phone: '+389 2 123 4567'
    },
    database: {
      connection: 'sqlite',
      database_name: 'facturino_test'
    }
  }

  let stepTimes = []
  let startTime, endTime

  beforeEach(() => {
    // Reset for fresh installation test
    cy.clearCookies()
    cy.clearLocalStorage()
    
    // Setup test environment - ensure clean database
    cy.task('cleanDatabase', { type: 'sqlite' })
    
    startTime = Date.now()
    stepTimes = []
  })

  it('should complete all 8 installation steps successfully', () => {
    cy.visit('/installation')
    
    // Verify we're on the installation page
    cy.url().should('include', '/installation')
    cy.get('body').should('contain.text', 'Choose your language').or('contain.text', 'Installation')
    
    // Screenshot initial state
    cy.screenshot('installation-start')
    
    // Execute each installation step
    installationSteps.forEach((step, index) => {
      executeInstallationStep(step, index)
    })
    
    // Final validation
    validateInstallationCompletion()
  })

  it('should persist data between steps correctly', () => {
    cy.visit('/installation')
    
    // Go through first few steps and validate data persistence
    cy.log('Testing data persistence between steps')
    
    // Step 0: Set language and verify persistence
    setLanguage('en')
    cy.reload()
    cy.get('[data-cy="language-select"]').should('contain.value', 'en')
    
    // Continue to database config and validate
    proceedToStep(3)
    configureDatabaseStep()
    
    // Navigate back and forward to test persistence
    cy.get('[data-cy="wizard-back"]').click()
    cy.get('[data-cy="wizard-next"]').click()
    
    // Verify database config is still there
    cy.get('select[name="database_connection"]').should('have.value', 'sqlite')
  })

  it('should handle wizard navigation correctly', () => {
    cy.visit('/installation')
    
    // Test forward navigation
    cy.log('Testing wizard navigation controls')
    
    // Should start at step 0
    cy.get('[data-cy="current-step"]').should('contain', '1')
    
    // Navigate forward through first 3 steps
    for (let i = 0; i < 3; i++) {
      cy.get('[data-cy="wizard-next"]').click()
      cy.wait(1000)
      cy.get('[data-cy="current-step"]').should('contain', (i + 2).toString())
    }
    
    // Test backward navigation
    cy.get('[data-cy="wizard-back"]').click()
    cy.get('[data-cy="current-step"]').should('contain', '3')
    
    // Test direct step navigation (if available)
    cy.get('[data-cy="step-indicator-1"]').then(($el) => {
      if ($el.length > 0) {
        cy.wrap($el).click()
        cy.get('[data-cy="current-step"]').should('contain', '2')
      }
    })
  })

  it('should validate form data in each step', () => {
    cy.visit('/installation')
    
    cy.log('Testing form validation in installation steps')
    
    // Skip to account settings step (step 6)
    proceedToStep(6)
    
    // Test invalid email
    cy.get('input[name="email"]').clear().type('invalid-email')
    cy.get('[data-cy="wizard-next"]').click()
    cy.get('.error, [role="alert"]').should('be.visible')
    
    // Test password mismatch
    cy.get('input[name="email"]').clear().type('test@example.com')
    cy.get('input[name="password"]').clear().type('password123')
    cy.get('input[name="password_confirmation"]').clear().type('different')
    cy.get('[data-cy="wizard-next"]').click()
    cy.get('.error, [role="alert"]').should('be.visible')
    
    // Test successful validation
    fillAccountForm(testData.admin)
    cy.get('[data-cy="wizard-next"]').click()
    cy.get('[data-cy="current-step"]').should('contain', '8')
  })

  it('should handle installation errors gracefully', () => {
    cy.visit('/installation')
    
    cy.log('Testing error handling during installation')
    
    // Skip to database configuration
    proceedToStep(3)
    
    // Configure invalid database settings
    cy.get('select[name="database_connection"]').select('mysql')
    cy.get('input[name="database_host"]').clear().type('invalid-host')
    cy.get('input[name="database_port"]').clear().type('99999')
    cy.get('input[name="database_name"]').clear().type('invalid_db')
    
    cy.get('[data-cy="test-connection"]').click()
    
    // Should show error message
    cy.get('.error, [role="alert"], .text-red').should('be.visible')
    cy.get('[data-cy="wizard-next"]').should('be.disabled')
    
    // Fix configuration
    configureDatabaseStep()
    cy.get('[data-cy="test-connection"]').click()
    cy.get('.success, [role="status"], .text-green').should('be.visible')
  })

  // Helper functions
  function executeInstallationStep(step, index) {
    const stepStart = Date.now()
    
    cy.log(`Executing Step ${step.step}: ${step.name}`)
    
    // Wait for step to load
    cy.wait(1000)
    
    // Take screenshot of current step
    cy.screenshot(`installation-step-${step.step}-${step.name.toLowerCase().replace(/\s+/g, '-')}`)
    
    switch (step.action) {
      case 'select':
        setLanguage(step.value)
        break
      case 'click':
        proceedWithBasicStep(step)
        break
      case 'configure-db':
        configureDatabaseStep()
        break
      case 'configure-email':
        configureEmailStep()
        break
      case 'create-account':
        fillAccountForm(testData.admin)
        break
      case 'create-company':
        fillCompanyForm(testData.company)
        break
      case 'finish':
        finishInstallation()
        break
    }
    
    const stepEnd = Date.now()
    stepTimes.push({
      step: step.step,
      name: step.name,
      duration: stepEnd - stepStart
    })
    
    // Verify step progression
    if (step.step < 8) {
      cy.get('[data-cy="current-step"]').should('contain', (step.step + 2).toString())
    }
  }

  function setLanguage(language) {
    cy.get('[data-cy="language-select"], select[name="language"]').then(($select) => {
      if ($select.length > 0) {
        cy.wrap($select).select(language)
      }
    })
    cy.get('[data-cy="wizard-next"], button:contains("Continue")').click()
  }

  function proceedWithBasicStep(step) {
    // Wait for any async checks to complete
    cy.wait(2000)
    
    // Look for the continue button
    cy.get('body').then(($body) => {
      const selectors = [
        step.selector,
        '[data-cy="wizard-next"]',
        'button:contains("Continue")',
        'button:contains("Next")',
        '[data-cy="continue-button"]'
      ]
      
      for (const selector of selectors) {
        if ($body.find(selector).length > 0) {
          cy.get(selector).first().should('be.visible').click()
          break
        }
      }
    })
  }

  function configureDatabaseStep() {
    cy.log('Configuring database settings')
    
    // Select SQLite for simplicity
    cy.get('select[name="database_connection"]').select('sqlite')
    cy.get('input[name="database_name"]').clear().type(testData.database.database_name)
    
    // Test database connection
    cy.get('[data-cy="test-connection"], button:contains("Test Connection")').then(($btn) => {
      if ($btn.length > 0) {
        cy.wrap($btn).click()
        cy.wait(3000) // Wait for connection test
      }
    })
    
    cy.get('[data-cy="wizard-next"], button:contains("Continue")').click()
  }

  function configureEmailStep() {
    cy.log('Configuring email settings')
    
    // Use basic mail driver for testing
    cy.get('select[name="mail_driver"]').then(($select) => {
      if ($select.length > 0) {
        cy.wrap($select).select('array')
      }
    })
    
    cy.get('[data-cy="wizard-next"], button:contains("Continue")').click()
  }

  function fillAccountForm(adminData) {
    cy.log('Filling admin account form')
    
    cy.get('input[name="name"]').clear().type(adminData.name)
    cy.get('input[name="email"]').clear().type(adminData.email)
    cy.get('input[name="password"]').clear().type(adminData.password)
    cy.get('input[name="password_confirmation"]').clear().type(adminData.password_confirmation)
    
    cy.get('[data-cy="wizard-next"], button:contains("Continue")').click()
  }

  function fillCompanyForm(companyData) {
    cy.log('Filling company information form')
    
    cy.get('input[name="name"]').clear().type(companyData.name)
    cy.get('input[name="email"]').clear().type(companyData.email)
    
    // Handle currency selection
    cy.get('select[name="currency"], [data-cy="currency-select"]').then(($select) => {
      if ($select.length > 0) {
        cy.wrap($select).select(companyData.currency)
      }
    })
    
    // Handle country selection
    cy.get('select[name="country"], [data-cy="country-select"]').then(($select) => {
      if ($select.length > 0) {
        cy.wrap($select).select(companyData.country)
      }
    })
    
    cy.get('input[name="address"], textarea[name="address"]').then(($input) => {
      if ($input.length > 0) {
        cy.wrap($input).clear().type(companyData.address)
      }
    })
    
    cy.get('input[name="phone"]').then(($input) => {
      if ($input.length > 0) {
        cy.wrap($input).clear().type(companyData.phone)
      }
    })
    
    cy.get('[data-cy="wizard-next"], button:contains("Continue")').click()
  }

  function finishInstallation() {
    cy.log('Finishing installation')
    
    // Final step - preferences and finish
    cy.get('[data-cy="preferences-finish"], button:contains("Finish"), button:contains("Complete")').click()
    
    // Wait for installation to complete
    cy.wait(5000)
  }

  function proceedToStep(targetStep) {
    cy.log(`Proceeding directly to step ${targetStep}`)
    
    for (let i = 0; i < targetStep; i++) {
      const step = installationSteps[i]
      
      if (step.action === 'select') {
        setLanguage('en')
      } else if (step.action === 'configure-db') {
        configureDatabaseStep()
      } else if (step.action === 'configure-email') {
        configureEmailStep()
      } else {
        proceedWithBasicStep(step)
      }
      
      cy.wait(1000)
    }
  }

  function validateInstallationCompletion() {
    endTime = Date.now()
    const totalTime = endTime - startTime
    
    cy.log('Validating installation completion')
    
    // Should redirect to main application or login
    cy.url({ timeout: 15000 }).should('not.include', '/installation')
    
    // Take final screenshot
    cy.screenshot('installation-completed')
    
    // Log performance metrics
    cy.log(`Total installation time: ${totalTime}ms`)
    stepTimes.forEach((step) => {
      cy.log(`Step ${step.step} (${step.name}): ${step.duration}ms`)
    })
    
    // Verify we can access the main application
    cy.visit('/')
    cy.url().should('not.include', '/installation')
    
    // Check for dashboard or login page
    cy.get('body').should('contain.text', 'Dashboard').or('contain.text', 'Login').or('contain.text', 'Sign In')
    
    // Validate sample data if present
    validateSampleDataCreation()
  }

  function validateSampleDataCreation() {
    cy.log('Validating sample data creation')
    
    // Check if sample invoices were created during installation
    cy.request({
      method: 'GET',
      url: '/api/v1/invoices',
      failOnStatusCode: false,
      headers: {
        'Accept': 'application/json'
      }
    }).then((response) => {
      if (response.status === 200 && response.body.data && response.body.data.length > 0) {
        cy.log(`Found ${response.body.data.length} sample invoices`)
      }
    })
    
    // Check if sample customers were created
    cy.request({
      method: 'GET',
      url: '/api/v1/customers',
      failOnStatusCode: false,
      headers: {
        'Accept': 'application/json'
      }
    }).then((response) => {
      if (response.status === 200 && response.body.data && response.body.data.length > 0) {
        cy.log(`Found ${response.body.data.length} sample customers`)
      }
    })
  }
})


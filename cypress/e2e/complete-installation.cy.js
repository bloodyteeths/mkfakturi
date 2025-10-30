// Complete Facturino installation
// This test walks through the entire installation process

describe('Complete Installation', () => {
  it('should complete the full installation process', () => {
    cy.visit('/installation')
    
    // Step 1: Language selection
    cy.get('body').should('contain.text', 'Choose your language')
    cy.get('select[name="language"], select').select('English')
    cy.get('button').contains('Continue').click()
    cy.wait(2000)
    
    // Step 2: Requirements check (if it exists)
    cy.url().then((url) => {
      if (url.includes('requirements')) {
        cy.log('Checking system requirements')
        cy.get('button').contains('Next').or('contains', 'Continue').click()
        cy.wait(2000)
      }
    })
    
    // Step 3: Database configuration
    cy.url().then((url) => {
      if (url.includes('database') || url.includes('environment')) {
        cy.log('Configuring database')
        
        // Fill database configuration
        cy.get('select[name*="database"], select[name*="connection"]').then(($select) => {
          if ($select.length > 0) {
            cy.wrap($select).select('PostgreSQL')
          }
        })
        
        cy.get('input[name*="host"], input[name*="hostname"]').then(($host) => {
          if ($host.length > 0) {
            cy.wrap($host).clear().type('db')
          }
        })
        
        cy.get('input[name*="port"]').then(($port) => {
          if ($port.length > 0) {
            cy.wrap($port).clear().type('5432')
          }
        })
        
        cy.get('input[name*="database_name"], input[name*="database"]').then(($dbName) => {
          if ($dbName.length > 0 && !$dbName.is('[name*="host"]') && !$dbName.is('[type="submit"]')) {
            cy.wrap($dbName).clear().type('facturino')
          }
        })
        
        cy.get('input[name*="username"], input[name*="user"]').then(($user) => {
          if ($user.length > 0 && !$user.is('[type="submit"]')) {
            cy.wrap($user).clear().type('facturino')
          }
        })
        
        cy.get('input[name*="password"]').then(($pass) => {
          if ($pass.length > 0 && !$pass.is('[type="submit"]')) {
            cy.wrap($pass).clear().type('secret')
          }
        })
        
        // Submit database config
        cy.get('button[type="submit"], button').contains('Continue').or('contains', 'Next').click()
        cy.wait(5000) // Wait for database migration
      }
    })
    
    // Step 4: Admin user creation
    cy.url().then((url) => {
      if (url.includes('user') || url.includes('admin') || url.includes('account')) {
        cy.log('Creating admin user')
        
        cy.get('input[name*="name"], input[name*="first_name"]').then(($name) => {
          if ($name.length > 0) {
            cy.wrap($name).clear().type('Admin User')
          }
        })
        
        cy.get('input[name*="email"]').then(($email) => {
          if ($email.length > 0) {
            cy.wrap($email).clear().type('admin@invoiceshelf.com')
          }
        })
        
        cy.get('input[name*="password"]:not([name*="confirmation"])').then(($adminPass) => {
          if ($adminPass.length > 0) {
            cy.wrap($adminPass).clear().type('password')
          }
        })
        
        cy.get('input[name*="password_confirmation"], input[name*="confirm"]').then(($confirm) => {
          if ($confirm.length > 0) {
            cy.wrap($confirm).clear().type('password')
          }
        })
        
        // Submit admin user
        cy.get('button[type="submit"], button').contains('Continue').or('contains', 'Create').or('contains', 'Finish').click()
        cy.wait(3000)
      }
    })
    
    // Step 5: Company setup (if exists)
    cy.url().then((url) => {
      if (url.includes('company') || url.includes('organization')) {
        cy.log('Setting up company info')
        
        cy.get('input[name*="name"], input[name*="company"]').then(($companyName) => {
          if ($companyName.length > 0) {
            cy.wrap($companyName).clear().type('Facturino Test Company')
          }
        })
        
        cy.get('input[name*="email"]').then(($companyEmail) => {
          if ($companyEmail.length > 0) {
            cy.wrap($companyEmail).clear().type('company@facturino.mk')
          }
        })
        
        // Submit company info
        cy.get('button[type="submit"], button').contains('Continue').or('contains', 'Finish').click()
        cy.wait(3000)
      }
    })
    
    // Final verification - should be redirected to dashboard or login
    cy.wait(5000)
    cy.url({ timeout: 10000 }).should('not.include', '/installation')
    
    // Take screenshot of final state
    cy.screenshot('installation-completed')
    
    // Try to access main application
    cy.visit('/')
    cy.url().then((finalUrl) => {
      if (finalUrl.includes('/login') || finalUrl.includes('/auth')) {
        cy.log('Installation completed - now showing login page')
      } else if (finalUrl.includes('/dashboard')) {
        cy.log('Installation completed - directly logged in to dashboard')
      } else {
        cy.log('Installation completed - final URL: ' + finalUrl)
      }
    })
  })
})
// Complete database setup for Facturino installation

describe('Complete Database Setup', () => {
  it('should complete database configuration and finish installation', () => {
    cy.visit('/installation')
    
    // Navigate through installation steps 
    cy.get('body').should('contain.text', 'Choose your language')
    cy.get('button').contains('Continue').click()
    cy.wait(3000)
    
    // Handle System Requirements step
    cy.get('body').should('contain.text', 'System Requirements')
    cy.get('button').contains('Continue').click()
    cy.wait(3000)
    
    // Handle Permissions step
    cy.get('body').should('contain.text', 'Permissions')
    cy.get('button').contains('Continue').click()
    cy.wait(3000)
    
    // Should now be on database configuration
    cy.get('body').should('contain.text', 'Site URL & Database')
    
    // Configure database settings
    // Database Connection - change from mysql to pgsql
    cy.get('select[name*="database"], select').select('pgsql')
    cy.wait(1000)
    
    // Database Host - change from 127.0.0.1 to 'db' 
    cy.get('input[name*="host"]').clear().type('db')
    
    // Database Port - change from 3306 to 5432
    cy.get('input[name*="port"]').clear().type('5432')
    
    // Database Name
    cy.get('input[name*="database_name"], input[name*="name"]:not([name*="username"])').clear().type('facturino')
    
    // Database Username
    cy.get('input[name*="username"]').clear().type('facturino')
    
    // Database Password
    cy.get('input[name*="password"]').clear().type('secret')
    
    // Submit database configuration
    cy.get('button').contains('Save & Continue').click()
    cy.wait(10000) // Wait for database migration
    
    // Take screenshot after database setup
    cy.screenshot('after-database-setup')
    
    // Continue through remaining steps
    cy.url().then((url) => {
      cy.log('URL after database setup: ' + url)
      
      // Handle admin user creation if present
      cy.get('body').then(($body) => {
        if ($body.text().includes('admin') || $body.text().includes('user') || $body.text().includes('account')) {
          cy.log('Setting up admin user')
          
          // Fill admin user details
          cy.get('input[name*="first_name"], input[name*="name"]').then(($name) => {
            if ($name.length > 0) {
              cy.wrap($name).first().clear().type('Admin')
            }
          })
          
          cy.get('input[name*="last_name"]').then(($lastName) => {
            if ($lastName.length > 0) {
              cy.wrap($lastName).clear().type('User')
            }
          })
          
          cy.get('input[name*="email"]').then(($email) => {
            if ($email.length > 0) {
              cy.wrap($email).clear().type('admin@invoiceshelf.com')
            }
          })
          
          cy.get('input[name*="password"]:not([name*="confirmation"])').then(($pass) => {
            if ($pass.length > 0) {
              cy.wrap($pass).clear().type('password')
            }
          })
          
          cy.get('input[name*="password_confirmation"], input[name*="confirm"]').then(($confirm) => {
            if ($confirm.length > 0) {
              cy.wrap($confirm).clear().type('password')
            }
          })
          
          // Submit admin user
          cy.get('button:contains("Continue"), button:contains("Save"), button[type="submit"]').click()
          cy.wait(5000)
          cy.screenshot('after-admin-setup')
        }
        
        // Handle company setup if present
        if ($body.text().includes('company') || $body.text().includes('organization')) {
          cy.log('Setting up company')
          
          cy.get('input[name*="name"], input[name*="company"]').then(($companyName) => {
            if ($companyName.length > 0) {
              cy.wrap($companyName).clear().type('Facturino Test Company')
            }
          })
          
          cy.get('button:contains("Continue"), button:contains("Finish"), button[type="submit"]').click()
          cy.wait(3000)
          cy.screenshot('after-company-setup')
        }
      })
    })
    
    // Final verification
    cy.wait(5000)
    cy.url({ timeout: 15000 }).should('not.include', '/installation')
    cy.screenshot('installation-complete-final')
    
    // Test if we can access the app
    cy.visit('/')
    cy.wait(3000)
    cy.screenshot('app-homepage')
  })
})
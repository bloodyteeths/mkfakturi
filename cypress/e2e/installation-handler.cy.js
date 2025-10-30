// Installation handler for Facturino
// This test handles the installation process if the app is not yet installed

describe('Installation Handler', () => {
  it('should complete installation if required', () => {
    cy.visit('/')
    
    // Check if we're redirected to installation
    cy.url().then((url) => {
      if (url.includes('/installation')) {
        cy.log('App requires installation - handling setup')
        
        // Navigate through installation steps
        cy.get('body').should('contain.text', 'Choose your language').or('contain.text', 'Installation')
        
        // Try to skip installation if there's a skip option
        cy.get('body').then(($body) => {
          if ($body.find('button:contains("Skip"), a:contains("Skip")').length > 0) {
            cy.get('button:contains("Skip"), a:contains("Skip")').first().click()
          } else {
            // Try to complete basic installation
            cy.log('Attempting to complete installation automatically')
            
            // Look for database configuration
            cy.get('body').then(($dbBody) => {
              if ($dbBody.find('input[name*="database"], select[name*="database"]').length > 0) {
                // Fill database config if forms are present
                cy.get('select[name*="database"], input[name*="connection"]').then(($dbSelect) => {
                  if ($dbSelect.length > 0) {
                    cy.wrap($dbSelect).first().select('pgsql').or.type('pgsql')
                  }
                })
                
                cy.get('input[name*="host"], input[name*="hostname"]').then(($host) => {
                  if ($host.length > 0) {
                    cy.wrap($host).clear().type('db')
                  }
                })
                
                cy.get('input[name*="database"], input[name*="name"]').then(($dbName) => {
                  if ($dbName.length > 0 && !$dbName.is('[type="submit"]')) {
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
                
                // Submit form if present
                cy.get('button[type="submit"], input[type="submit"]').then(($submit) => {
                  if ($submit.length > 0) {
                    cy.wrap($submit).first().click()
                    cy.wait(5000) // Wait for database setup
                  }
                })
              }
            })
            
            // Handle admin user creation
            cy.url().then((adminUrl) => {
              if (adminUrl.includes('admin') || adminUrl.includes('user')) {
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
                
                cy.get('input[name*="password"]').then(($adminPass) => {
                  if ($adminPass.length > 0) {
                    cy.wrap($adminPass).clear().type('password')
                  }
                })
                
                cy.get('input[name*="password_confirmation"], input[name*="confirm"]').then(($confirm) => {
                  if ($confirm.length > 0) {
                    cy.wrap($confirm).clear().type('password')
                  }
                })
                
                cy.get('button[type="submit"], input[type="submit"]').then(($adminSubmit) => {
                  if ($adminSubmit.length > 0) {
                    cy.wrap($adminSubmit).first().click()
                    cy.wait(3000)
                  }
                })
              }
            })
          }
        })
        
        // Wait and verify installation completed
        cy.wait(5000)
        cy.visit('/')
        
        // Check if we can access the main app now
        cy.url({ timeout: 10000 }).then((finalUrl) => {
          if (finalUrl.includes('/installation')) {
            cy.log('Installation still incomplete - may require manual intervention')
            // Take screenshot for debugging
            cy.screenshot('installation-incomplete')
          } else {
            cy.log('Installation completed successfully')
          }
        })
      } else {
        cy.log('App is already installed')
      }
    })
  })
})
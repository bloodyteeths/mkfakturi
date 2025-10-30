// Simple installation test for Facturino
// This test directly interacts with the visible UI elements

describe('Simple Installation', () => {
  it('should navigate through installation steps', () => {
    cy.visit('/installation')
    
    // Step 1: Language selection - click Continue button
    cy.get('body').should('contain.text', 'Choose your language')
    cy.get('button').contains('Continue').click()
    cy.wait(3000)
    
    // Take screenshot of next step
    cy.screenshot('installation-step-2')
    
    // Continue through any subsequent steps
    cy.url().then((url) => {
      cy.log('Current URL after language selection: ' + url)
      
      // Try to continue if there are more steps
      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Continue"), button:contains("Next")').length > 0) {
          cy.get('button:contains("Continue"), button:contains("Next")').first().click()
          cy.wait(3000)
          cy.screenshot('installation-step-3')
        }
        
        // Keep going if there are more steps
        if ($body.find('button:contains("Continue"), button:contains("Next"), button:contains("Finish")').length > 0) {
          cy.get('button:contains("Continue"), button:contains("Next"), button:contains("Finish")').first().click()
          cy.wait(3000)
          cy.screenshot('installation-step-4')
        }
      })
    })
    
    // Final check - wait and see where we end up
    cy.wait(5000)
    cy.url({ timeout: 10000 }).then((finalUrl) => {
      cy.log('Final URL: ' + finalUrl)
      cy.screenshot('installation-final')
      
      // If still on installation, try to find and fill any forms
      if (finalUrl.includes('/installation')) {
        cy.get('body').then(($body) => {
          // Look for any input forms and try to fill them
          if ($body.find('input[type="email"]').length > 0) {
            cy.get('input[type="email"]').first().clear().type('admin@facturino.mk')
          }
          if ($body.find('input[type="password"]').length > 0) {
            cy.get('input[type="password"]').first().clear().type('password')
          }
          if ($body.find('input[name*="name"]').length > 0) {
            cy.get('input[name*="name"]').first().clear().type('Admin User')
          }
          
          // Try to submit
          if ($body.find('button[type="submit"], button:contains("Continue"), button:contains("Finish")').length > 0) {
            cy.get('button[type="submit"], button:contains("Continue"), button:contains("Finish")').first().click()
            cy.wait(3000)
            cy.screenshot('installation-after-submit')
          }
        })
      }
    })
  })
})
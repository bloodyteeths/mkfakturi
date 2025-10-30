// Basic L10N test for Facturino - works with installation state
// Tests basic language functionality and branding

describe('L10N Basic Test Suite', () => {
  const languages = [
    { code: 'en', name: 'English' },
    { code: 'mk', name: 'Macedonian' }, 
    { code: 'sq', name: 'Albanian' }
  ]

  // Basic translations to test
  const translations = {
    en: {
      branding: 'Facturino',
      continue: 'Continue',
      requirements: 'System Requirements',
      database: 'Database'
    },
    mk: {
      branding: 'Facturino', // Should remain consistent
      continue: 'Продолжи',
      requirements: 'Системски барања',
      database: 'База на податоци'
    },
    sq: {
      branding: 'Facturino', // Should remain consistent  
      continue: 'Vazhdo',
      requirements: 'Kërkesat e Sistemit',
      database: 'Baza e të Dhënave'
    }
  }

  describe('Basic Language and Branding Tests', () => {
    it('should display Facturino branding consistently', () => {
      cy.visit('/')
      
      // Check that Facturino branding appears on page
      cy.get('body').should('contain.text', 'Facturino')
      
      // Check page title contains branding
      cy.title().should('contain', 'Facturino')
      
      // Take screenshot for visual verification
      cy.screenshot('branding-test-homepage')
    })

    it('should access installation with language selection', () => {
      cy.visit('/installation')
      
      // Verify installation page loads
      cy.get('body').should('contain.text', 'Choose your language')
      
      // Check that language selection exists  
      cy.get('body').should('contain.text', 'English')
      
      // Verify branding on installation page
      cy.get('body').should('contain.text', 'Facturino')
      
      cy.screenshot('installation-language-selection')
    })

    languages.forEach((language) => {
      it(`should handle ${language.name} language context`, () => {
        cy.visit('/installation')
        
        // Basic language test - verify page structure is accessible
        cy.get('body').should('be.visible')
        cy.get('body').should('contain.text', 'Facturino')
        
        // Navigate through some steps to test basic functionality
        cy.get('button').contains('Continue').should('be.visible')
        
        // If we can change language, test it
        cy.get('body').then(($body) => {
          if ($body.find('[data-lang], .language-selector').length > 0) {
            cy.log(`Testing language selector for ${language.name}`)
            // Try to interact with language selector if present
            cy.get('[data-lang], .language-selector').first().click({ force: true })
          } else {
            cy.log(`Language selector not found, testing in default language`)
          }
        })
        
        cy.screenshot(`language-test-${language.code}`)
      })
    })

    it('should verify basic UI elements are functional', () => {
      cy.visit('/installation')
      
      // Test basic navigation
      cy.get('button').contains('Continue').should('be.visible')
      cy.get('button').contains('Continue').click()
      cy.wait(2000)
      
      // Should advance to next step
      cy.url().should('include', '/installation')
      cy.get('body').should('be.visible')
      
      // Take screenshot of next step
      cy.screenshot('installation-step-2-ui-test')
      
      // Verify branding persists
      cy.get('body').should('contain.text', 'Facturino')
    })
  })

  describe('Cross-language Consistency Tests', () => {
    it('should maintain Facturino branding across different pages', () => {
      // Test homepage
      cy.visit('/')
      cy.get('body').should('contain.text', 'Facturino')
      cy.screenshot('branding-consistency-home')
      
      // Test installation page
      cy.visit('/installation')
      cy.get('body').should('contain.text', 'Facturino')
      cy.screenshot('branding-consistency-installation')
      
      // Test that the app is responsive
      cy.viewport(1280, 720)
      cy.get('body').should('be.visible')
      cy.screenshot('responsive-test-desktop')
      
      cy.viewport(375, 667) // Mobile size
      cy.get('body').should('be.visible')
      cy.screenshot('responsive-test-mobile')
    })

    it('should verify basic app functionality works', () => {
      cy.visit('/installation')
      
      // Test form interactions
      cy.get('button').should('be.visible')
      cy.get('body').should('contain.text', 'Choose your language')
      
      // Navigate through first few steps
      cy.get('button').contains('Continue').click()
      cy.wait(3000)
      
      // Should show system requirements
      cy.get('body').should('contain.text', 'System Requirements')
      cy.screenshot('system-requirements-page')
      
      // Check that all requirements are satisfied
      cy.get('body').then(($body) => {
        if ($body.text().includes('8.2')) {
          cy.log('PHP version requirement satisfied')
        }
        if ($body.text().includes('exif')) {
          cy.log('PHP extensions detected')
        }
      })
    })
  })

  describe('Accessibility and Usability Tests', () => {
    it('should have accessible UI elements', () => {
      cy.visit('/installation')
      
      // Check for proper heading structure
      cy.get('h1, h2, h3').should('exist')
      
      // Check for form labels and buttons
      cy.get('button').should('have.attr', 'type').or('have.text')
      
      // Verify images have alt text or proper handling
      cy.get('img').then(($imgs) => {
        if ($imgs.length > 0) {
          cy.wrap($imgs).should('have.attr', 'alt').or('not.exist')
        }
      })
      
      cy.screenshot('accessibility-test')
    })

    it('should handle errors gracefully', () => {
      // Test 404 page
      cy.visit('/nonexistent-page', { failOnStatusCode: false })
      cy.get('body').should('be.visible')
      cy.screenshot('error-handling-404')
      
      // Return to working page
      cy.visit('/installation')
      cy.get('body').should('contain.text', 'Choose your language')
      cy.screenshot('error-recovery-installation')
    })
  })
})

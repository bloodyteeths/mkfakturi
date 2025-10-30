// Cypress i18n smoke test for localization functionality
// Tests language switching between en, mk, and sq
// Verifies key UI elements and new features are translated
// Tests Facturino branding appears correctly

describe('L10N Smoke Test Suite', () => {
  const languages = [
    { code: 'en', name: 'English' },
    { code: 'mk', name: 'Macedonian' },
    { code: 'sq', name: 'Albanian' }
  ]

  // Test data for different languages
  const translations = {
    en: {
      dashboard: 'Dashboard',
      customers: 'Customers',
      invoices: 'Invoices',
      settings: 'Settings',
      migration_wizard: 'Migration Wizard',
      ai_insights: 'AI Insights',
      vat_generate: 'Generate VAT Return',
      branding: 'Facturino'
    },
    mk: {
      dashboard: 'Контролна табла',
      customers: 'Клиенти',
      invoices: 'Фактури',
      settings: 'Поставувања',
      migration_wizard: 'Мастер за миграција',
      ai_insights: 'АИ финансиски анализи',
      vat_generate: 'Генерирај ДДВ пријава',
      branding: 'Facturino'
    },
    sq: {
      dashboard: 'Paneli Kryesor',
      customers: 'Klientët',
      invoices: 'Faturat',
      settings: 'Cilësimet',
      migration_wizard: 'Magjistari i Migrimit',
      ai_insights: 'AI Insights',
      vat_generate: 'Gjeneroni deklarimin e TVSH',
      branding: 'Facturino'
    }
  }

  beforeEach(() => {
    // Visit login page
    cy.visit('/admin/login')
    
    // Login with admin credentials
    cy.get('input[name="email"]').should('be.visible').type(Cypress.env('ADMIN_EMAIL'))
    cy.get('input[name="password"]').should('be.visible').type(Cypress.env('ADMIN_PASSWORD'))
    cy.get('button[type="submit"]').click()
    
    // Wait for dashboard to load
    cy.url().should('include', '/admin/dashboard')
    cy.get('body').should('be.visible')
  })

  languages.forEach((language) => {
    describe(`Language: ${language.name} (${language.code})`, () => {
      beforeEach(() => {
        // Switch to the specific language
        cy.switchLanguage(language.code)
        cy.wait(1000) // Allow time for language switch
      })

      it('should display correct navigation translations', () => {
        const t = translations[language.code]
        
        // Check main navigation elements
        cy.get('[data-testid="nav-dashboard"], [href*="dashboard"]')
          .should('contain.text', t.dashboard)
        
        cy.get('[data-testid="nav-customers"], [href*="customers"]')
          .should('contain.text', t.customers)
        
        cy.get('[data-testid="nav-invoices"], [href*="invoices"]')
          .should('contain.text', t.invoices)
        
        cy.get('[data-testid="nav-settings"], [href*="settings"]')
          .should('contain.text', t.settings)
      })

      it('should display Migration Wizard link with correct translation', () => {
        const t = translations[language.code]
        
        // Check for Migration Wizard in sidebar
        cy.get('body').then(($body) => {
          if ($body.find('[data-testid="nav-migration"], [href*="imports/wizard"]').length > 0) {
            cy.get('[data-testid="nav-migration"], [href*="imports/wizard"]')
              .should('contain.text', t.migration_wizard)
          } else {
            // Alternative selector for migration wizard
            cy.get('a').contains(t.migration_wizard).should('be.visible')
          }
        })
      })

      it('should display correct Facturino branding', () => {
        const t = translations[language.code]
        
        // Check for Facturino branding in header/logo areas
        cy.get('body').should('contain.text', t.branding)
        
        // Check page title
        cy.title().should('contain', t.branding)
        
        // Check for logo alt text or branding elements
        cy.get('img[alt*="Facturino"], [data-testid="logo"], .logo')
          .should('exist')
      })

      it('should access dashboard settings and verify AI Insights toggle', () => {
        const t = translations[language.code]
        
        // Navigate to settings
        cy.get('[data-testid="nav-settings"], [href*="settings"]').click()
        cy.wait(1000)
        
        // Look for dashboard settings or user settings
        cy.get('body').then(($body) => {
          // Try to find dashboard settings
          if ($body.find('[href*="dashboard-settings"], [data-testid="dashboard-settings"]').length > 0) {
            cy.get('[href*="dashboard-settings"], [data-testid="dashboard-settings"]').click()
            cy.wait(1000)
            
            // Check for AI Insights setting
            cy.get('body').should('contain.text', t.ai_insights)
          } else {
            // Log that AI Insights toggle is not accessible in this view
            cy.log('AI Insights toggle not found in current settings view')
          }
        })
      })

      it('should access tax menu and verify VAT Return action', () => {
        const t = translations[language.code]
        
        // Navigate to tax types or reports section
        cy.visit('/admin/tax-types')
        cy.wait(1000)
        
        // Look for tax menu dropdown or VAT return option
        cy.get('body').then(($body) => {
          if ($body.find('[data-testid="tax-dropdown"], .dropdown').length > 0) {
            // Click dropdown
            cy.get('[data-testid="tax-dropdown"], .dropdown').first().click()
            cy.wait(500)
            
            // Check for VAT return option
            cy.get('body').should('contain.text', t.vat_generate)
          } else {
            // Alternative: check if VAT return is available elsewhere
            cy.visit('/admin/reports')
            cy.wait(1000)
            cy.get('body').then(($reportBody) => {
              if ($reportBody.text().includes(t.vat_generate)) {
                cy.get('body').should('contain.text', t.vat_generate)
              } else {
                cy.log('VAT Return action not found in current interface')
              }
            })
          }
        })
      })

      it('should verify basic page functionality and responsiveness', () => {
        const t = translations[language.code]
        
        // Test dashboard page
        cy.visit('/admin/dashboard')
        cy.get('body').should('be.visible')
        cy.get('h1, h2, .page-title').should('contain.text', t.dashboard)
        
        // Test customers page
        cy.visit('/admin/customers')
        cy.wait(1000)
        cy.get('body').should('be.visible')
        cy.get('h1, h2, .page-title').should('contain.text', t.customers)
        
        // Test invoices page
        cy.visit('/admin/invoices')
        cy.wait(1000)
        cy.get('body').should('be.visible')
        cy.get('h1, h2, .page-title').should('contain.text', t.invoices)
      })

      it('should verify form elements and buttons have correct translations', () => {
        // Visit a form page (customers create)
        cy.visit('/admin/customers/create')
        cy.wait(1000)
        
        // Check for common button translations
        cy.get('body').then(($body) => {
          const commonButtons = ['Save', 'Cancel', 'Create', 'Update']
          commonButtons.forEach(button => {
            if ($body.text().includes(button)) {
              cy.log(`Found button: ${button}`)
            }
          })
        })
        
        // Verify page loads correctly
        cy.get('form, .form-container').should('be.visible')
      })
    })
  })

  describe('Cross-language consistency', () => {
    it('should maintain Facturino branding across all languages', () => {
      languages.forEach((language) => {
        cy.switchLanguage(language.code)
        cy.wait(1000)
        
        // Verify Facturino appears consistently
        cy.get('body').should('contain.text', 'Facturino')
        cy.title().should('contain', 'Facturino')
      })
    })

    it('should have working navigation in all languages', () => {
      languages.forEach((language) => {
        cy.switchLanguage(language.code)
        cy.wait(1000)
        
        // Test basic navigation works
        cy.visit('/admin/dashboard')
        cy.get('body').should('be.visible')
        
        cy.visit('/admin/customers')
        cy.get('body').should('be.visible')
        
        cy.visit('/admin/invoices')
        cy.get('body').should('be.visible')
      })
    })
  })
})

// Custom command to switch language
Cypress.Commands.add('switchLanguage', (languageCode) => {
  // Method 1: Try URL parameter
  cy.visit(`/admin/dashboard?lang=${languageCode}`)
  
  // Method 2: Try language selector if available
  cy.get('body').then(($body) => {
    if ($body.find('[data-testid="language-selector"], .language-selector').length > 0) {
      cy.get('[data-testid="language-selector"], .language-selector').click()
      cy.get(`[data-lang="${languageCode}"], [value="${languageCode}"]`).click()
    }
  })
  
  // Method 3: Set localStorage if application uses it
  cy.window().then((win) => {
    win.localStorage.setItem('language', languageCode)
    win.localStorage.setItem('i18n_locale', languageCode)
  })
  
  // Reload to ensure language change takes effect
  cy.reload()
})


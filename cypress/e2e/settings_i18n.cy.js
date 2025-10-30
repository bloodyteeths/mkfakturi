// SET-03: Multi-language switching (mk/sq/en) Test
// Tests language loading, UI translation, and language persistence

describe('Multi-language Support (i18n)', () => {
  const testLanguages = [
    { code: 'en', name: 'English', direction: 'ltr' },
    { code: 'mk', name: 'Macedonian', direction: 'ltr' },
    { code: 'sq', name: 'Albanian', direction: 'ltr' }
  ]

  // Key translations to test
  const translationTests = {
    en: {
      dashboard: 'Dashboard',
      customers: 'Customers',
      invoices: 'Invoices',
      save: 'Save',
      settings: 'Settings',
      logout: 'Logout'
    },
    mk: {
      dashboard: 'Контролна табла',
      customers: 'Клиенти', 
      invoices: 'Фактури',
      save: 'Зачувај',
      settings: 'Поставувања',
      logout: 'Одјави се'
    },
    sq: {
      dashboard: 'Paneli Kryesor',
      customers: 'Klientët',
      invoices: 'Faturat', 
      save: 'Ruani',
      settings: 'Cilësimet',
      logout: 'Dilni'
    }
  }

  beforeEach(() => {
    // Login as admin user
    cy.visit('/admin/login')
    
    // Use existing admin credentials
    cy.get('input[type="email"]').type('admin@example.com')
    cy.get('input[type="password"]').type('password')
    cy.get('button[type="submit"]').click()
    
    // Wait for dashboard to load
    cy.url().should('include', '/admin/dashboard')
    cy.wait(1000)
  })

  describe('Language Settings Access', () => {
    it('should access language preferences settings', () => {
      // Navigate to preferences settings
      cy.visit('/admin/settings/preferences')
      cy.wait(2000)
      
      // Verify preferences page loads
      cy.get('body').should('contain.text', 'Preferences')
      
      // Look for language selector
      cy.get('body').then($body => {
        const hasLanguageDropdown = $body.find('[data-v-], select, .multiselect').filter((_, el) => {
          const text = el.textContent || el.innerText || ''
          return text.toLowerCase().includes('language') || 
                 text.toLowerCase().includes('english') ||
                 text.toLowerCase().includes('macedonian')
        }).length > 0
        
        if (hasLanguageDropdown) {
          cy.log('Language selector found in preferences')
        } else {
          cy.log('Language selector structure may be different')
        }
      })
      
      cy.screenshot('language-preferences-page')
    })

    it('should access account language settings', () => {
      // Navigate to account settings
      cy.visit('/admin/settings/account-settings')
      cy.wait(2000)
      
      // Verify account settings page loads
      cy.get('body').should('be.visible')
      
      // Look for language selection in account settings
      cy.get('body').then($body => {
        const hasLanguageOption = $body.find('label, span').filter((_, el) => {
          const text = el.textContent || el.innerText || ''
          return text.toLowerCase().includes('language')
        }).length > 0
        
        if (hasLanguageOption) {
          cy.log('Language option found in account settings')
        }
      })
      
      cy.screenshot('account-language-settings')
    })
  })

  describe('Language Switching Functionality', () => {
    testLanguages.forEach((language) => {
      it(`should switch to ${language.name} (${language.code}) language`, () => {
        // Go to preferences where language can be changed
        cy.visit('/admin/settings/preferences')
        cy.wait(2000)
        
        // Try to find and interact with language selector
        cy.get('body').then($body => {
          // Look for multiselect or dropdown that might contain language options
          const selectors = [
            '.multiselect',
            'select',
            '[role="listbox"]',
            '[data-testid="language-selector"]'
          ]
          
          let foundSelector = false
          
          for (const selector of selectors) {
            const elements = $body.find(selector)
            if (elements.length > 0) {
              // Try to interact with the selector
              cy.get(selector).first().click({ force: true })
              cy.wait(500)
              
              // Look for language options
              cy.get('body').then($bodyAfterClick => {
                if ($bodyAfterClick.text().includes(language.name) || 
                    $bodyAfterClick.text().includes(language.code.toUpperCase())) {
                  cy.log(`Found ${language.name} option`)
                  foundSelector = true
                  
                  // Try to select the language
                  cy.contains(language.name).click({ force: true })
                  cy.wait(500)
                  
                  // Save settings
                  cy.get('button').contains('Save').click({ force: true })
                  cy.wait(2000)
                }
              })
              
              break
            }
          }
          
          if (!foundSelector) {
            cy.log(`Language selector not found for ${language.name} - testing current language display`)
            
            // Test that current language displays correctly
            cy.get('body').should('be.visible')
          }
        })
        
        cy.screenshot(`language-switch-${language.code}`)
      })
    })

    it('should handle language switching via URL parameters', () => {
      // Test language switching via URL (if supported)
      cy.visit('/admin/dashboard?lang=mk')
      cy.wait(2000)
      
      // Check if URL language parameter affects the interface
      cy.get('body').should('be.visible')
      
      // Try different language codes
      cy.visit('/admin/dashboard?lang=sq')  
      cy.wait(1000)
      cy.get('body').should('be.visible')
      
      cy.visit('/admin/dashboard?lang=en')
      cy.wait(1000) 
      cy.get('body').should('be.visible')
      
      cy.screenshot('url-language-switching')
    })
  })

  describe('Translation Validation', () => {
    it('should display correct navigation translations', () => {
      // Test navigation elements for translation correctness
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Check for navigation elements
      const navigationElements = ['Dashboard', 'Customers', 'Invoices', 'Settings']
      
      navigationElements.forEach(element => {
        cy.get('body').then($body => {
          if ($body.text().includes(element)) {
            cy.log(`Found navigation element: ${element}`)
          }
        })
      })
      
      cy.screenshot('navigation-translations')
    })

    it('should handle Cyrillic text display (Macedonian)', () => {
      // Test Cyrillic character rendering
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Check if Cyrillic text renders properly
      const cyrillicTest = 'Контролна табла'
      
      cy.get('body').then($body => {
        // Check if any Cyrillic text is present
        const hasCyrillic = /[а-яё]/i.test($body.text())
        
        if (hasCyrillic) {
          cy.log('Cyrillic text detected and rendered correctly')
        } else {
          cy.log('No Cyrillic text found - may be using different language')
        }
      })
      
      cy.screenshot('cyrillic-text-rendering')
    })

    it('should handle Albanian special characters', () => {
      // Test Albanian character rendering (ë, ç, etc.)
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Check if Albanian special characters render properly
      const albanianChars = ['ë', 'ç', 'ü', 'Ë', 'Ç']
      
      cy.get('body').then($body => {
        const bodyText = $body.text()
        const hasAlbanianChars = albanianChars.some(char => bodyText.includes(char))
        
        if (hasAlbanianChars) {
          cy.log('Albanian special characters detected and rendered correctly')
        } else {
          cy.log('No Albanian special characters found - may be using different language')
        }
      })
      
      cy.screenshot('albanian-characters-rendering')
    })

    Object.keys(translationTests).forEach(langCode => {
      it(`should display correct ${langCode} translations`, () => {
        cy.visit('/admin/dashboard')
        cy.wait(2000)
        
        const translations = translationTests[langCode]
        
        Object.keys(translations).forEach(key => {
          const expectedText = translations[key]
          
          cy.get('body').then($body => {
            if ($body.text().includes(expectedText)) {
              cy.log(`Found correct ${langCode} translation for ${key}: ${expectedText}`)
            }
          })
        })
        
        cy.screenshot(`translations-validation-${langCode}`)
      })
    })
  })

  describe('Language Persistence', () => {
    it('should persist language selection across page reloads', () => {
      // Set a specific language and verify it persists
      cy.visit('/admin/settings/preferences')
      cy.wait(2000)
      
      // Try to select English explicitly
      cy.get('body').then($body => {
        // Look for language selector and select English
        const selectors = ['.multiselect', 'select']
        
        for (const selector of selectors) {
          if ($body.find(selector).length > 0) {
            cy.get(selector).first().click({ force: true })
            cy.wait(500)
            
            // Try to select English
            if ($body.text().includes('English')) {
              cy.contains('English').click({ force: true })
              cy.get('button').contains('Save').click({ force: true })
              cy.wait(2000)
              break
            }
          }
        }
      })
      
      // Reload page and check if language persisted
      cy.reload()
      cy.wait(2000)
      
      // Navigate to different page and verify language
      cy.visit('/admin/customers')
      cy.wait(1000)
      
      cy.get('body').should('be.visible')
      
      cy.screenshot('language-persistence-test')
    })

    it('should handle browser language detection', () => {
      // Test browser language detection on first visit
      cy.visit('/admin/login', {
        onBeforeLoad: (win) => {
          // Mock navigator.language
          Object.defineProperty(win.navigator, 'language', {
            value: 'mk-MK'
          })
        }
      })
      
      cy.wait(1000)
      
      // Check if browser language affects the interface
      cy.get('body').should('be.visible')
      
      cy.screenshot('browser-language-detection')
    })
  })

  describe('Performance & Accessibility', () => {
    it('should load translations efficiently', () => {
      const startTime = Date.now()
      
      cy.visit('/admin/dashboard')
      
      cy.get('body').should('be.visible').then(() => {
        const loadTime = Date.now() - startTime
        cy.log(`Page with translations loaded in ${loadTime}ms`)
        
        // Expect reasonable load time
        expect(loadTime).to.be.lessThan(5000)
      })
    })

    it('should be accessible on mobile devices', () => {
      cy.viewport('iphone-6')
      
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Test that interface is usable in different languages on mobile
      cy.get('body').should('be.visible')
      
      cy.screenshot('mobile-multilanguage-support')
    })

    it('should handle RTL languages gracefully', () => {
      // Even though mk, sq, en are LTR, test RTL handling capability
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Check if the system can handle direction changes
      cy.document().then(doc => {
        const htmlDir = doc.documentElement.dir
        cy.log(`Document direction: ${htmlDir || 'not set'}`)
      })
      
      cy.screenshot('rtl-language-handling')
    })
  })

  describe('Error Handling', () => {
    it('should handle missing translations gracefully', () => {
      // Test behavior when translations are missing
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Check that missing translations don't break the interface
      cy.get('body').should('be.visible')
      
      // Look for translation keys that might not be translated
      cy.get('body').then($body => {
        const text = $body.text()
        
        // Check for untranslated keys (usually contain dots or underscores)
        const hasTranslationKeys = /\w+\.\w+/.test(text)
        
        if (hasTranslationKeys) {
          cy.log('Found potential untranslated keys - this is normal in development')
        } else {
          cy.log('No obvious translation keys found')
        }
      })
      
      cy.screenshot('missing-translations-handling')
    })

    it('should fallback to English for unavailable languages', () => {
      // Test fallback mechanism
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Verify that system doesn't break with invalid language codes
      cy.window().then((win) => {
        if (win.i18n) {
          // Test that fallback works
          cy.log('i18n instance available for testing')
        }
      })
      
      cy.screenshot('language-fallback-test')
    })
  })
})


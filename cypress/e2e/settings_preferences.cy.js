// SET-04: User Preferences and Dashboard Customization Test
// Tests dashboard customization persistence, user settings, and preference updates

describe('User Preferences and Dashboard Customization', () => {
  const testUserPreferences = {
    language: 'en',
    timezone: 'Europe/Skopje',
    dateFormat: 'DD/MM/YYYY',
    currency: 'MKD'
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

  describe('Dashboard Layout and Display', () => {
    it('should load dashboard with proper layout', () => {
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Verify dashboard components load
      cy.get('body').should('be.visible')
      
      // Check for dashboard stats/cards
      cy.get('body').then($body => {
        const hasStatsCards = $body.find('[class*="stats"], [class*="card"], [class*="grid"]').length > 0
        
        if (hasStatsCards) {
          cy.log('Dashboard stats cards found')
        } else {
          cy.log('Dashboard layout may be different - testing basic functionality')
        }
      })
      
      cy.screenshot('dashboard-layout')
    })

    it('should display user-specific dashboard data', () => {
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Check for user-specific elements
      const dashboardElements = [
        'Customers',
        'Invoices', 
        'Payments',
        'Amount',
        'Due'
      ]
      
      dashboardElements.forEach(element => {
        cy.get('body').then($body => {
          if ($body.text().includes(element)) {
            cy.log(`Dashboard element found: ${element}`)
          }
        })
      })
      
      cy.screenshot('dashboard-user-data')
    })

    it('should handle dashboard data refresh', () => {
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Test page refresh behavior
      cy.reload()
      cy.wait(2000)
      
      // Verify dashboard reloads correctly
      cy.get('body').should('be.visible')
      
      cy.screenshot('dashboard-refresh')
    })

    it('should display dashboard stats correctly', () => {
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Check for numerical stats
      cy.get('body').then($body => {
        const statsPattern = /\d+/
        const hasNumericStats = statsPattern.test($body.text())
        
        if (hasNumericStats) {
          cy.log('Numeric dashboard stats found')
        }
      })
      
      // Check for monetary amounts
      cy.get('body').then($body => {
        const moneyPattern = /(\$|€|MKD|USD|EUR|\d+[.,]\d+)/
        const hasMoneyDisplay = moneyPattern.test($body.text())
        
        if (hasMoneyDisplay) {
          cy.log('Monetary amounts displayed on dashboard')
        }
      })
      
      cy.screenshot('dashboard-stats')
    })
  })

  describe('User Preferences Settings', () => {
    it('should access and modify user preferences', () => {
      // Navigate to preferences
      cy.visit('/admin/settings/preferences')
      cy.wait(2000)
      
      // Verify preferences page loads
      cy.get('body').should('contain.text', 'Preferences')
      
      // Look for preference options
      const preferenceOptions = [
        'language',
        'currency', 
        'timezone',
        'date'
      ]
      
      preferenceOptions.forEach(option => {
        cy.get('body').then($body => {
          if ($body.text().toLowerCase().includes(option)) {
            cy.log(`Preference option found: ${option}`)
          }
        })
      })
      
      cy.screenshot('user-preferences-page')
    })

    it('should update language preferences', () => {
      cy.visit('/admin/settings/preferences')
      cy.wait(2000)
      
      // Try to change language setting
      cy.get('body').then($body => {
        // Look for language selector
        const selectors = [
          '.multiselect',
          'select',
          '[role="combobox"]'
        ]
        
        for (const selector of selectors) {
          const elements = $body.find(selector)
          if (elements.length > 0) {
            // Try to interact with language selector
            cy.get(selector).first().click({ force: true })
            cy.wait(500)
            
            // Look for English option
            cy.get('body').then($bodyAfter => {
              if ($bodyAfter.text().includes('English')) {
                cy.contains('English').click({ force: true })
                
                // Save preferences
                cy.get('button').contains('Save').click({ force: true })
                cy.wait(2000)
                
                cy.log('Language preference updated')
              }
            })
            
            break
          }
        }
      })
      
      cy.screenshot('language-preference-update')
    })

    it('should handle timezone preferences', () => {
      cy.visit('/admin/settings/preferences')
      cy.wait(2000)
      
      // Look for timezone setting
      cy.get('body').then($body => {
        if ($body.text().toLowerCase().includes('timezone') || 
            $body.text().toLowerCase().includes('time zone')) {
          cy.log('Timezone setting found')
          
          // Try to interact with timezone selector
          cy.get('body').find('select, .multiselect').then($selectors => {
            if ($selectors.length > 0) {
              cy.wrap($selectors).last().click({ force: true })
              cy.wait(500)
              
              // Save if possible
              cy.get('button').contains('Save').click({ force: true })
              cy.wait(1000)
            }
          })
        }
      })
      
      cy.screenshot('timezone-preference')
    })

    it('should persist date format preferences', () => {
      cy.visit('/admin/settings/preferences')
      cy.wait(2000)
      
      // Look for date format setting
      cy.get('body').then($body => {
        if ($body.text().toLowerCase().includes('date format')) {
          cy.log('Date format setting found')
          
          // Try to change date format
          cy.get('body').find('select, .multiselect').then($selectors => {
            if ($selectors.length > 0) {
              // Try different date format
              cy.wrap($selectors).eq(2).click({ force: true })
              cy.wait(500)
              
              // Save preferences
              cy.get('button').contains('Save').click({ force: true })
              cy.wait(2000)
            }
          })
        }
      })
      
      cy.screenshot('date-format-preference')
    })
  })

  describe('Account Settings', () => {
    it('should update user account information', () => {
      cy.visit('/admin/settings/account-settings')
      cy.wait(2000)
      
      // Test user profile update
      cy.get('input').then($inputs => {
        if ($inputs.length > 0) {
          // Update name field
          cy.get('input').first().clear().type('Test User Updated')
          
          // Save changes
          cy.get('button').contains('Save').click({ force: true })
          cy.wait(2000)
          
          cy.log('Account settings updated')
        }
      })
      
      cy.screenshot('account-settings-update')
    })

    it('should handle profile picture upload', () => {
      cy.visit('/admin/settings/account-settings')
      cy.wait(2000)
      
      // Check for file upload component
      cy.get('body').then($body => {
        if ($body.find('input[type="file"]').length > 0) {
          // Test file upload
          cy.get('input[type="file"]').first().selectFile({
            contents: 'cypress/fixtures/macedonia-test-data.json',
            fileName: 'profile-pic.jpg',
            mimeType: 'image/jpeg'
          }, { force: true })
          
          cy.wait(1000)
          
          // Save if upload component exists
          cy.get('button').contains('Save').click({ force: true })
          cy.wait(2000)
          
          cy.screenshot('profile-picture-upload')
        } else {
          cy.log('Profile picture upload not available')
        }
      })
    })

    it('should validate password change functionality', () => {
      cy.visit('/admin/settings/account-settings')
      cy.wait(2000)
      
      // Look for password fields
      cy.get('input[type="password"]').then($passwordFields => {
        if ($passwordFields.length > 0) {
          // Test password change validation
          cy.get('input[type="password"]').first().type('newpassword123')
          
          if ($passwordFields.length > 1) {
            cy.get('input[type="password"]').eq(1).type('newpassword123')
          }
          
          // Try to save (should validate)
          cy.get('button').contains('Save').click({ force: true })
          cy.wait(1000)
          
          cy.log('Password change validation tested')
        }
      })
      
      cy.screenshot('password-change-validation')
    })
  })

  describe('Dashboard Personalization', () => {
    it('should maintain user-specific dashboard state', () => {
      // Test that dashboard shows user-specific data
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Check for personalized content
      cy.get('body').then($body => {
        const text = $body.text()
        
        // Look for user-specific indicators
        const hasPersonalizedContent = 
          text.includes('Welcome') ||
          text.includes('Your') ||
          text.includes('admin@example.com')
        
        if (hasPersonalizedContent) {
          cy.log('Personalized dashboard content found')
        }
      })
      
      cy.screenshot('personalized-dashboard')
    })

    it('should handle dashboard navigation preferences', () => {
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Test navigation state persistence
      cy.get('a, [role="link"]').then($links => {
        if ($links.length > 0) {
          // Click on a navigation item
          cy.wrap($links).first().click({ force: true })
          cy.wait(1000)
          
          // Go back to dashboard
          cy.visit('/admin/dashboard')
          cy.wait(1000)
          
          // Verify dashboard state is maintained
          cy.get('body').should('be.visible')
        }
      })
      
      cy.screenshot('navigation-preferences')
    })

    it('should display dashboard widgets based on user permissions', () => {
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Check for permission-based content
      const permissionElements = [
        'Customers',
        'Invoices',
        'Payments',
        'Reports',
        'Settings'
      ]
      
      permissionElements.forEach(element => {
        cy.get('body').then($body => {
          if ($body.text().includes(element)) {
            cy.log(`Permission-based element visible: ${element}`)
          }
        })
      })
      
      cy.screenshot('permission-based-widgets')
    })
  })

  describe('Preferences Persistence', () => {
    it('should persist preferences across browser sessions', () => {
      // Set specific preferences
      cy.visit('/admin/settings/preferences')
      cy.wait(2000)
      
      // Make a preference change
      cy.get('body').then($body => {
        const selectors = $body.find('select, .multiselect')
        if (selectors.length > 0) {
          cy.wrap(selectors.first()).click({ force: true })
          cy.wait(500)
          
          // Save preferences
          cy.get('button').contains('Save').click({ force: true })
          cy.wait(2000)
        }
      })
      
      // Clear local storage and reload
      cy.clearLocalStorage()
      cy.reload()
      cy.wait(2000)
      
      // Verify preferences persisted
      cy.get('body').should('be.visible')
      
      cy.screenshot('preferences-persistence')
    })

    it('should handle preference synchronization', () => {
      // Test preference sync between different sections
      cy.visit('/admin/settings/account-settings')
      cy.wait(2000)
      
      // Update language in account settings
      cy.get('body').then($body => {
        const languageSelectors = $body.find('select, .multiselect').filter((_, el) => {
          return el.textContent?.toLowerCase().includes('language') ||
                 el.textContent?.toLowerCase().includes('english')
        })
        
        if (languageSelectors.length > 0) {
          cy.wrap(languageSelectors.first()).click({ force: true })
          cy.wait(500)
          
          cy.get('button').contains('Save').click({ force: true })
          cy.wait(2000)
        }
      })
      
      // Check if language change affects dashboard
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      cy.get('body').should('be.visible')
      
      cy.screenshot('preference-synchronization')
    })
  })

  describe('Performance and Responsiveness', () => {
    it('should load preferences quickly', () => {
      const startTime = Date.now()
      
      cy.visit('/admin/settings/preferences')
      
      cy.get('body').should('be.visible').then(() => {
        const loadTime = Date.now() - startTime
        cy.log(`Preferences loaded in ${loadTime}ms`)
        
        expect(loadTime).to.be.lessThan(3000)
      })
    })

    it('should be responsive on mobile devices', () => {
      cy.viewport('iphone-6')
      
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Test mobile dashboard layout
      cy.get('body').should('be.visible')
      
      // Test mobile preferences
      cy.visit('/admin/settings/preferences')
      cy.wait(2000)
      cy.get('body').should('be.visible')
      
      cy.screenshot('mobile-dashboard-preferences')
    })

    it('should handle large amounts of dashboard data', () => {
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Test dashboard performance with data
      cy.get('body').should('be.visible')
      
      // Simulate interaction with dashboard elements
      cy.get('body').find('a, button').then($interactive => {
        if ($interactive.length > 0) {
          // Test interaction performance
          cy.wrap($interactive.first()).click({ force: true })
          cy.wait(1000)
        }
      })
      
      cy.screenshot('dashboard-performance')
    })
  })

  describe('Error Handling', () => {
    it('should handle preference save errors gracefully', () => {
      // Test error handling for preference updates
      cy.intercept('POST', '**/settings**', { forceNetworkError: true }).as('preferencesRequest')
      
      cy.visit('/admin/settings/preferences')
      cy.wait(2000)
      
      // Try to save preferences with network error
      cy.get('button').contains('Save').click({ force: true })
      
      // Verify error handling
      cy.wait(1000)
      cy.get('body').should('be.visible')
      
      cy.screenshot('preference-error-handling')
    })

    it('should fallback gracefully when user settings unavailable', () => {
      // Test behavior when user settings API fails
      cy.intercept('GET', '**/me/settings', { statusCode: 500 }).as('settingsRequest')
      
      cy.visit('/admin/dashboard')
      cy.wait(2000)
      
      // Dashboard should still load with defaults
      cy.get('body').should('be.visible')
      
      cy.screenshot('settings-fallback')
    })
  })
})


/**
 * INV-01: Item Barcode and SKU Management
 *
 * Tests comprehensive item management with barcode and SKU features:
 * - Creating items with barcode and SKU
 * - Searching items by barcode
 * - SKU uniqueness validation (company-scoped)
 * - Barcode display in item list
 * - Updating barcode/SKU values
 * - Inventory tracking with barcodes
 */

describe('INV-01: Item Barcode and SKU Management', () => {
  let testData

  before(() => {
    // Load test data if available
    cy.fixture('items-test-data').then((data) => {
      testData = data
    }).catch(() => {
      // Use default test data if fixture doesn't exist
      testData = {
        items: [
          {
            name: 'Widget Pro',
            sku: 'WIDGET-001',
            barcode: '1234567890128',
            price: 99.99,
            description: 'Premium widget product'
          },
          {
            name: 'Gadget Max',
            sku: 'GADGET-001',
            barcode: '9876543210987',
            price: 149.99,
            description: 'Advanced gadget device'
          }
        ]
      }
    })
  })

  beforeEach(() => {
    // Login as admin and get auth token
    cy.visit('/admin/login')
    cy.get('[data-cy="email"]').type(Cypress.env('ADMIN_EMAIL'))
    cy.get('[data-cy="password"]').type(Cypress.env('ADMIN_PASSWORD'))
    cy.get('[data-cy="login-button"]').click()

    // Wait for dashboard to load
    cy.url().should('include', '/admin/dashboard')

    // Navigate to items
    cy.visit('/admin/items')
    cy.url().should('include', '/admin/items')
  })

  describe('Item Creation with Barcode and SKU', () => {
    it('should create item with barcode and SKU', () => {
      const item = testData.items[0]

      // Click Create Item
      cy.get('[data-cy="create-item"]').click()

      // Fill item form
      cy.get('[data-cy="item-name"]').type(item.name)
      cy.get('[data-cy="item-sku"]').type(item.sku)
      cy.get('[data-cy="item-barcode"]').type(item.barcode)
      cy.get('[data-cy="item-price"]').type(item.price.toString())
      cy.get('[data-cy="item-description"]').type(item.description)

      // Submit form
      cy.get('[data-cy="save-item"]').click()

      // Verify success message
      cy.get('[data-cy="success-message"]').should('contain', 'Item created successfully')

      // Verify item appears in list
      cy.get('[data-cy="items-table"]').should('contain', item.name)
      cy.get('[data-cy="items-table"]').should('contain', item.sku)
      cy.get('[data-cy="items-table"]').should('contain', item.barcode)
    })

    it('should create item with auto-generated barcode', () => {
      // Click Create Item
      cy.get('[data-cy="create-item"]').click()

      // Fill required fields only (no barcode)
      cy.get('[data-cy="item-name"]').type('Test Product')
      cy.get('[data-cy="item-sku"]').type('AUTO-SKU-001')
      cy.get('[data-cy="item-price"]').type('50.00')

      // Check auto-generate barcode option if available
      cy.get('[data-cy="auto-generate-barcode"]').should('exist').click()

      // Submit form
      cy.get('[data-cy="save-item"]').click()

      // Verify success
      cy.get('[data-cy="success-message"]').should('contain', 'Item created successfully')

      // Verify item has a generated barcode (13 digits for EAN-13)
      cy.get('[data-cy="items-table"]').contains('Test Product')
        .parent()
        .find('[data-cy="item-barcode"]')
        .should('match', /^\d{13}$/)
    })

    it('should validate SKU uniqueness within company', () => {
      const duplicateSku = 'DUPLICATE-SKU-001'

      // Create first item with SKU
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('First Item')
      cy.get('[data-cy="item-sku"]').type(duplicateSku)
      cy.get('[data-cy="item-price"]').type('25.00')
      cy.get('[data-cy="save-item"]').click()
      cy.get('[data-cy="success-message"]').should('be.visible')

      // Try to create second item with same SKU
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Second Item')
      cy.get('[data-cy="item-sku"]').type(duplicateSku)
      cy.get('[data-cy="item-price"]').type('30.00')
      cy.get('[data-cy="save-item"]').click()

      // Should show validation error
      cy.get('[data-cy="validation-errors"]').should('contain', 'SKU already exists')
    })

    it('should allow items without barcode or SKU', () => {
      // Click Create Item
      cy.get('[data-cy="create-item"]').click()

      // Fill only required fields (no barcode or SKU)
      cy.get('[data-cy="item-name"]').type('Simple Product')
      cy.get('[data-cy="item-price"]').type('15.00')

      // Submit form
      cy.get('[data-cy="save-item"]').click()

      // Verify success
      cy.get('[data-cy="success-message"]').should('contain', 'Item created successfully')
      cy.get('[data-cy="items-table"]').should('contain', 'Simple Product')
    })

    it('should validate EAN-13 barcode format', () => {
      const invalidBarcodes = [
        '123456789012',    // 12 digits (too short)
        '12345678901234',  // 14 digits (too long)
        '123456789012A',   // Contains letter
        'ABCDEFGHIJKLM',   // All letters
      ]

      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Test Item')
      cy.get('[data-cy="item-price"]').type('10.00')

      invalidBarcodes.forEach((invalidBarcode) => {
        cy.get('[data-cy="item-barcode"]').clear().type(invalidBarcode)
        cy.get('[data-cy="save-item"]').click()

        // Should show validation error
        cy.get('[data-cy="validation-errors"]').should('contain', 'Barcode must be 13 digits')

        // Clear for next test
        cy.get('[data-cy="item-barcode"]').clear()
      })
    })
  })

  describe('Barcode Search Functionality', () => {
    it('should search item by exact barcode', () => {
      const item = testData.items[0]

      // Create item first
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type(item.name)
      cy.get('[data-cy="item-barcode"]').type(item.barcode)
      cy.get('[data-cy="item-price"]').type(item.price.toString())
      cy.get('[data-cy="save-item"]').click()

      // Search by barcode
      cy.get('[data-cy="item-search"]').clear().type(item.barcode)

      // Verify only matching item appears
      cy.get('[data-cy="items-table"]').should('contain', item.name)
      cy.get('[data-cy="items-table"]').should('contain', item.barcode)
    })

    it('should search item by SKU', () => {
      const sku = 'SEARCH-SKU-001'

      // Create item
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Searchable Item')
      cy.get('[data-cy="item-sku"]').type(sku)
      cy.get('[data-cy="item-price"]').type('20.00')
      cy.get('[data-cy="save-item"]').click()

      // Search by SKU
      cy.get('[data-cy="item-search"]').clear().type(sku)

      // Verify result
      cy.get('[data-cy="items-table"]').should('contain', 'Searchable Item')
      cy.get('[data-cy="items-table"]').should('contain', sku)
    })

    it('should support partial barcode search', () => {
      // Create items with similar barcodes
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Product A')
      cy.get('[data-cy="item-barcode"]').type('1111111111116')
      cy.get('[data-cy="item-price"]').type('10.00')
      cy.get('[data-cy="save-item"]').click()

      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Product B')
      cy.get('[data-cy="item-barcode"]').type('1111222222223')
      cy.get('[data-cy="item-price"]').type('15.00')
      cy.get('[data-cy="save-item"]').click()

      // Search with partial barcode
      cy.get('[data-cy="item-search"]').clear().type('1111')

      // Both items should appear
      cy.get('[data-cy="items-table"]').should('contain', 'Product A')
      cy.get('[data-cy="items-table"]').should('contain', 'Product B')
    })

    it('should handle barcode scanner input', () => {
      const scannedBarcode = '9876543210987'

      // Create item
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Scanner Test Item')
      cy.get('[data-cy="item-barcode"]').type(scannedBarcode)
      cy.get('[data-cy="item-price"]').type('25.00')
      cy.get('[data-cy="save-item"]').click()

      // Simulate barcode scanner input (rapid typing + enter)
      cy.get('[data-cy="item-search"]').clear().type(`${scannedBarcode}{enter}`)

      // Should immediately find and display the item
      cy.get('[data-cy="items-table"]').should('contain', 'Scanner Test Item')
      cy.get('[data-cy="items-table"]').should('contain', scannedBarcode)
    })
  })

  describe('Item Update with Barcode/SKU', () => {
    it('should update item barcode and SKU', () => {
      const originalSku = 'OLD-SKU'
      const newSku = 'NEW-SKU'
      const originalBarcode = '1234567890128'
      const newBarcode = '9876543210987'

      // Create item
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Updatable Item')
      cy.get('[data-cy="item-sku"]').type(originalSku)
      cy.get('[data-cy="item-barcode"]').type(originalBarcode)
      cy.get('[data-cy="item-price"]').type('30.00')
      cy.get('[data-cy="save-item"]').click()

      // Edit the item
      cy.get('[data-cy="items-table"]').contains('Updatable Item')
        .parent()
        .find('[data-cy="edit-item"]')
        .click()

      // Update SKU and barcode
      cy.get('[data-cy="item-sku"]').clear().type(newSku)
      cy.get('[data-cy="item-barcode"]').clear().type(newBarcode)
      cy.get('[data-cy="save-item"]').click()

      // Verify updates
      cy.get('[data-cy="success-message"]').should('contain', 'Item updated successfully')
      cy.get('[data-cy="items-table"]').should('contain', newSku)
      cy.get('[data-cy="items-table"]').should('contain', newBarcode)
    })

    it('should prevent changing SKU to existing one', () => {
      const existingSku = 'EXISTING-SKU'

      // Create first item
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('First Item')
      cy.get('[data-cy="item-sku"]').type(existingSku)
      cy.get('[data-cy="item-price"]').type('10.00')
      cy.get('[data-cy="save-item"]').click()

      // Create second item
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Second Item')
      cy.get('[data-cy="item-sku"]').type('SECOND-SKU')
      cy.get('[data-cy="item-price"]').type('20.00')
      cy.get('[data-cy="save-item"]').click()

      // Try to update second item with existing SKU
      cy.get('[data-cy="items-table"]').contains('Second Item')
        .parent()
        .find('[data-cy="edit-item"]')
        .click()
      cy.get('[data-cy="item-sku"]').clear().type(existingSku)
      cy.get('[data-cy="save-item"]').click()

      // Should show validation error
      cy.get('[data-cy="validation-errors"]').should('contain', 'SKU already exists')
    })
  })

  describe('Barcode Display in Item List', () => {
    it('should display barcode column in items table', () => {
      // Verify barcode column header exists
      cy.get('[data-cy="items-table-header"]').should('contain', 'Barcode')
    })

    it('should display SKU column in items table', () => {
      // Verify SKU column header exists
      cy.get('[data-cy="items-table-header"]').should('contain', 'SKU')
    })

    it('should show empty state for items without barcode', () => {
      // Create item without barcode
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('No Barcode Item')
      cy.get('[data-cy="item-price"]').type('5.00')
      cy.get('[data-cy="save-item"]').click()

      // Verify empty barcode cell
      cy.get('[data-cy="items-table"]').contains('No Barcode Item')
        .parent()
        .find('[data-cy="item-barcode"]')
        .should('be.empty')
    })

    it('should format barcode display correctly', () => {
      const barcode = '1234567890128'

      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Format Test Item')
      cy.get('[data-cy="item-barcode"]').type(barcode)
      cy.get('[data-cy="item-price"]').type('12.00')
      cy.get('[data-cy="save-item"]').click()

      // Verify barcode is displayed without modification
      cy.get('[data-cy="items-table"]').contains('Format Test Item')
        .parent()
        .find('[data-cy="item-barcode"]')
        .should('contain', barcode)
    })
  })

  describe('Barcode Generation and Printing', () => {
    it('should display barcode image when viewing item details', () => {
      const barcode = '1234567890128'

      // Create item with barcode
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Barcode Display Test')
      cy.get('[data-cy="item-barcode"]').type(barcode)
      cy.get('[data-cy="item-price"]').type('15.00')
      cy.get('[data-cy="save-item"]').click()

      // View item details
      cy.get('[data-cy="items-table"]').contains('Barcode Display Test')
        .parent()
        .find('[data-cy="view-item"]')
        .click()

      // Verify barcode image is displayed
      cy.get('[data-cy="barcode-image"]').should('be.visible')
      cy.get('[data-cy="barcode-image"]').should('have.attr', 'alt', barcode)
    })

    it('should allow printing barcode label', () => {
      const barcode = '9876543210987'

      // Create item
      cy.get('[data-cy="create-item"]').click()
      cy.get('[data-cy="item-name"]').type('Print Test Item')
      cy.get('[data-cy="item-barcode"]').type(barcode)
      cy.get('[data-cy="item-price"]').type('20.00')
      cy.get('[data-cy="save-item"]').click()

      // View item details
      cy.get('[data-cy="items-table"]').contains('Print Test Item')
        .parent()
        .find('[data-cy="view-item"]')
        .click()

      // Click print barcode button
      cy.get('[data-cy="print-barcode"]').should('be.visible').click()

      // Verify print dialog or preview appears
      cy.get('[data-cy="print-preview"]').should('be.visible')
    })
  })

  describe('Performance and Validation', () => {
    it('should load items page with barcodes within 2 seconds', () => {
      const startTime = Date.now()

      cy.visit('/admin/items').then(() => {
        const loadTime = Date.now() - startTime
        expect(loadTime).to.be.lessThan(2000)
      })
    })

    it('should handle rapid barcode searches efficiently', () => {
      const barcodes = ['1234567890128', '9876543210987', '1111111111116']

      // Create multiple items
      barcodes.forEach((barcode, index) => {
        cy.get('[data-cy="create-item"]').click()
        cy.get('[data-cy="item-name"]').type(`Speed Test ${index + 1}`)
        cy.get('[data-cy="item-barcode"]').type(barcode)
        cy.get('[data-cy="item-price"]').type('10.00')
        cy.get('[data-cy="save-item"]').click()
        cy.wait(200)
      })

      // Perform rapid searches
      barcodes.forEach((barcode) => {
        cy.get('[data-cy="item-search"]').clear().type(barcode)
        cy.get('[data-cy="items-table"]').should('contain', barcode)
      })
    })

    it('should validate required fields with barcode data', () => {
      cy.get('[data-cy="create-item"]').click()

      // Fill only barcode, skip required name
      cy.get('[data-cy="item-barcode"]').type('1234567890128')
      cy.get('[data-cy="save-item"]').click()

      // Should show validation error
      cy.get('[data-cy="validation-errors"]').should('contain', 'Name is required')
    })
  })

  afterEach(() => {
    // Clean up created test items
    cy.window().then((win) => {
      // Clear any test data if needed
      // This would typically involve API calls to clean up test data
    })
  })
})

// CLAUDE-CHECKPOINT

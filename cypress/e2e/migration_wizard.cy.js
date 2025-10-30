/**
 * OPS-05: Universal Migration Wizard Complete Flow
 * 
 * Tests the complete migration workflow from external systems with Macedonia-specific data:
 * - Onivo accounting system import
 * - Megasoft ERP import  
 * - CSV file format support
 * - Field mapping validation
 * - Data transformation and validation
 * - Import progress tracking
 * - Error handling and rollback
 */

describe('OPS-05: Universal Migration Wizard Complete Flow', () => {
  let macedoniaData
  let sampleMigrationData
  
  before(() => {
    // Load Macedonia test data
    cy.fixture('macedonia-test-data').then((data) => {
      macedoniaData = data
    })
    
    // Prepare sample migration files
    cy.prepareMigrationTestFiles()
  })

  beforeEach(() => {
    // Login as admin
    cy.visit('/admin/login')
    cy.get('[data-cy="email"]').type(Cypress.env('ADMIN_EMAIL'))
    cy.get('[data-cy="password"]').type(Cypress.env('ADMIN_PASSWORD'))
    cy.get('[data-cy="login-button"]').click()
    
    // Wait for dashboard
    cy.url().should('include', '/admin/dashboard')
  })

  describe('Migration Wizard Initiation', () => {
    it('should display migration wizard with system options', () => {
      cy.visit('/admin/migration/wizard')
      
      // Verify wizard steps are displayed
      cy.get('[data-cy="wizard-steps"]').should('be.visible')
      cy.get('[data-cy="step-1"]').should('contain', 'Choose Source System')
      cy.get('[data-cy="step-2"]').should('contain', 'Upload Files')
      cy.get('[data-cy="step-3"]').should('contain', 'Map Fields')
      cy.get('[data-cy="step-4"]').should('contain', 'Preview & Import')
      
      // Verify supported systems
      cy.get('[data-cy="source-systems"]').should('be.visible')
      cy.get('[data-cy="system-onivo"]').should('contain', 'Onivo Accounting')
      cy.get('[data-cy="system-megasoft"]').should('contain', 'Megasoft ERP')
      cy.get('[data-cy="system-csv"]').should('contain', 'Generic CSV')
      cy.get('[data-cy="system-excel"]').should('contain', 'Excel Files')
      
      // Check Macedonia-specific information
      cy.get('[data-cy="migration-info"]').should('contain', 'Macedonian')
      cy.get('[data-cy="migration-info"]').should('contain', 'ДДВ')
      cy.get('[data-cy="migration-info"]').should('contain', 'MKD')
    })

    it('should select Onivo as source system', () => {
      cy.visit('/admin/migration/wizard')
      
      // Select Onivo system
      cy.get('[data-cy="system-onivo"]').click()
      
      // Verify Onivo-specific options appear
      cy.get('[data-cy="onivo-version"]').should('be.visible')
      cy.get('[data-cy="onivo-database-type"]').should('be.visible')
      
      // Select Onivo version
      cy.get('[data-cy="onivo-version"]').select('2024')
      cy.get('[data-cy="onivo-database-type"]').select('sql_server')
      
      // Verify migration capabilities
      cy.get('[data-cy="supported-entities"]').should('contain', 'Customers')
      cy.get('[data-cy="supported-entities"]').should('contain', 'Invoices')
      cy.get('[data-cy="supported-entities"]').should('contain', 'Items')
      cy.get('[data-cy="supported-entities"]').should('contain', 'Payments')
      
      // Proceed to next step
      cy.get('[data-cy="next-step"]').click()
      cy.get('[data-cy="current-step"]').should('contain', 'Upload Files')
    })

    it('should select Megasoft as source system', () => {
      cy.visit('/admin/migration/wizard')
      
      // Select Megasoft system
      cy.get('[data-cy="system-megasoft"]').click()
      
      // Verify Megasoft-specific options
      cy.get('[data-cy="megasoft-module"]').should('be.visible')
      cy.get('[data-cy="megasoft-export-format"]').should('be.visible')
      
      // Configure Megasoft import
      cy.get('[data-cy="megasoft-module"]').select('accounting')
      cy.get('[data-cy="megasoft-export-format"]').select('xml')
      
      // Check Macedonia support
      cy.get('[data-cy="megasoft-features"]').should('contain', 'Macedonia VAT support')
      cy.get('[data-cy="megasoft-features"]').should('contain', 'Cyrillic characters')
      
      cy.get('[data-cy="next-step"]').click()
    })
  })

  describe('File Upload and Validation', () => {
    it('should upload Onivo export files', () => {
      cy.startMigrationWizard('onivo')
      
      // Upload multiple Onivo files
      const onivoFiles = [
        'onivo_customers.csv',
        'onivo_invoices.csv',
        'onivo_items.csv',
        'onivo_payments.csv'
      ]
      
      onivoFiles.forEach((filename) => {
        cy.get('[data-cy="file-upload"]').selectFile(`cypress/fixtures/migration/${filename}`)
        cy.get('[data-cy="uploaded-files"]').should('contain', filename)
      })
      
      // Validate file structure
      cy.get('[data-cy="validate-files"]').click()
      
      // Verify validation results
      cy.get('[data-cy="validation-results"]').should('be.visible')
      cy.get('[data-cy="customers-count"]').should('contain', '15 customers found')
      cy.get('[data-cy="invoices-count"]').should('contain', '45 invoices found')
      cy.get('[data-cy="items-count"]').should('contain', '120 items found')
      cy.get('[data-cy="payments-count"]').should('contain', '38 payments found')
      
      // Check for Macedonia-specific data
      cy.get('[data-cy="validation-info"]').should('contain', 'Macedonia VAT rates detected')
      cy.get('[data-cy="validation-info"]').should('contain', 'Cyrillic characters preserved')
      
      cy.get('[data-cy="next-step"]').click()
    })

    it('should upload Megasoft XML export', () => {
      cy.startMigrationWizard('megasoft')
      
      // Upload Megasoft XML file
      cy.get('[data-cy="file-upload"]').selectFile('cypress/fixtures/migration/megasoft_export.xml')
      
      // Validate XML structure
      cy.get('[data-cy="validate-files"]').click()
      
      // Verify XML parsing results
      cy.get('[data-cy="xml-structure"]').should('be.visible')
      cy.get('[data-cy="xml-entities"]').should('contain', 'Companies: 2')
      cy.get('[data-cy="xml-entities"]').should('contain', 'Customers: 28')
      cy.get('[data-cy="xml-entities"]').should('contain', 'Documents: 156')
      
      // Check XML namespace compatibility
      cy.get('[data-cy="xml-validation"]').should('contain', 'Valid Megasoft XML format')
      cy.get('[data-cy="xml-validation"]').should('contain', 'Macedonia localization detected')
      
      cy.get('[data-cy="next-step"]').click()
    })

    it('should handle file upload errors', () => {
      cy.startMigrationWizard('csv')
      
      // Try to upload invalid file
      cy.get('[data-cy="file-upload"]').selectFile('cypress/fixtures/migration/invalid_format.txt')
      
      // Should show error message
      cy.get('[data-cy="upload-error"]').should('contain', 'Unsupported file format')
      
      // Try to upload corrupted CSV
      cy.get('[data-cy="file-upload"]').selectFile('cypress/fixtures/migration/corrupted.csv')
      cy.get('[data-cy="validate-files"]').click()
      
      cy.get('[data-cy="validation-errors"]').should('contain', 'File appears to be corrupted')
      cy.get('[data-cy="validation-errors"]').should('contain', 'Invalid CSV structure')
    })

    it('should validate file size limits', () => {
      cy.startMigrationWizard('csv')
      
      // Test file size validation (mock large file)
      cy.mockLargeFileUpload('large_dataset.csv', 50) // 50MB
      
      cy.get('[data-cy="file-size-warning"]').should('contain', 'Large file detected')
      cy.get('[data-cy="file-size-warning"]').should('contain', 'Processing may take longer')
      
      // Test file size limit exceeded
      cy.mockLargeFileUpload('huge_dataset.csv', 200) // 200MB
      
      cy.get('[data-cy="upload-error"]').should('contain', 'File size exceeds maximum limit')
    })
  })

  describe('Field Mapping Configuration', () => {
    it('should auto-map Onivo fields to InvoiceShelf fields', () => {
      cy.startMigrationWizard('onivo')
      cy.uploadMigrationFiles(['onivo_customers.csv'])
      
      // Auto-mapping should occur
      cy.get('[data-cy="auto-map-fields"]').click()
      
      // Verify customer field mappings
      cy.get('[data-cy="mapping-customers"]').within(() => {
        cy.get('[data-cy="source-field"]').should('contain', 'customer_name')
        cy.get('[data-cy="target-field"]').should('contain', 'name')
        
        cy.get('[data-cy="source-field"]').should('contain', 'vat_number')
        cy.get('[data-cy="target-field"]').should('contain', 'tax_id')
        
        cy.get('[data-cy="source-field"]').should('contain', 'address_line1')
        cy.get('[data-cy="target-field"]').should('contain', 'address_street_1')
      })
      
      // Check Macedonia-specific mappings
      cy.get('[data-cy="mapping-tax"]').within(() => {
        cy.get('[data-cy="ddv-18-mapping"]').should('be.visible')
        cy.get('[data-cy="ddv-5-mapping"]').should('be.visible')
        cy.get('[data-cy="ddv-exempt-mapping"]').should('be.visible')
      })
      
      // Verify mapping confidence scores
      cy.get('[data-cy="mapping-confidence"]').should('contain', 'High confidence: 95%')
    })

    it('should handle manual field mapping corrections', () => {
      cy.startMigrationWizard('megasoft')
      cy.uploadMigrationFiles(['megasoft_export.xml'])
      
      // Manual mapping adjustments
      cy.get('[data-cy="mapping-invoices"]').within(() => {
        // Fix incorrect auto-mapping
        cy.get('[data-cy="source-invoice-date"]').parent().find('[data-cy="target-select"]').select('invoice_date')
        cy.get('[data-cy="source-due-date"]').parent().find('[data-cy="target-select"]').select('due_date')
        
        // Map Megasoft-specific fields
        cy.get('[data-cy="source-megasoft-id"]').parent().find('[data-cy="target-select"]').select('external_id')
        cy.get('[data-cy="source-document-type"]').parent().find('[data-cy="target-select"]').select('notes')
      })
      
      // Configure transformation rules
      cy.get('[data-cy="transformation-rules"]').click()
      cy.get('[data-cy="date-format"]').select('dd/mm/yyyy') // Megasoft date format
      cy.get('[data-cy="currency-handling"]').select('convert_to_mkd')
      cy.get('[data-cy="vat-rate-mapping"]').select('macedonia_standard')
      
      // Save mapping configuration
      cy.get('[data-cy="save-mapping"]').click()
      cy.get('[data-cy="mapping-saved"]').should('be.visible')
    })

    it('should validate field mapping completeness', () => {
      cy.startMigrationWizard('csv')
      cy.uploadMigrationFiles(['custom_customers.csv'])
      
      // Leave some fields unmapped
      cy.get('[data-cy="validate-mapping"]').click()
      
      // Should show validation warnings
      cy.get('[data-cy="mapping-warnings"]').should('contain', 'Required fields not mapped')
      cy.get('[data-cy="missing-fields"]').should('contain', 'Customer email')
      cy.get('[data-cy="missing-fields"]').should('contain', 'Tax ID')
      
      // Fix required mappings
      cy.get('[data-cy="source-email-field"]').parent().find('[data-cy="target-select"]').select('email')
      cy.get('[data-cy="source-tax-field"]').parent().find('[data-cy="target-select"]').select('tax_id')
      
      // Re-validate
      cy.get('[data-cy="validate-mapping"]').click()
      cy.get('[data-cy="mapping-valid"]').should('contain', 'All required fields mapped')
      
      cy.get('[data-cy="next-step"]').click()
    })

    it('should preview data transformation', () => {
      cy.startMigrationWizard('onivo')
      cy.uploadMigrationFiles(['onivo_customers.csv'])
      cy.autoMapFields()
      
      // Preview data transformation
      cy.get('[data-cy="preview-transformation"]').click()
      
      // Verify preview table
      cy.get('[data-cy="preview-table"]').should('be.visible')
      cy.get('[data-cy="preview-rows"]').should('have.length.at.least', 5)
      
      // Check Macedonia data transformation
      cy.get('[data-cy="preview-table"]').within(() => {
        // Verify VAT ID format transformation
        cy.get('[data-cy="tax-id-preview"]').should('contain', 'MK')
        
        // Verify Cyrillic character preservation
        cy.get('[data-cy="name-preview"]').should('contain', 'Македонска')
        
        // Verify address formatting
        cy.get('[data-cy="address-preview"]').should('contain', 'Скопје')
      })
      
      // Check transformation statistics
      cy.get('[data-cy="transformation-stats"]').should('contain', 'Records to import: 15')
      cy.get('[data-cy="transformation-stats"]').should('contain', 'Valid records: 14')
      cy.get('[data-cy="transformation-stats"]').should('contain', 'Warnings: 1')
    })
  })

  describe('Import Execution and Progress', () => {
    it('should execute complete Onivo migration with progress tracking', () => {
      cy.startCompleteOnivaMigration()
      
      // Start import process
      cy.get('[data-cy="start-import"]').click()
      
      // Verify import progress
      cy.get('[data-cy="import-progress"]').should('be.visible')
      cy.get('[data-cy="progress-bar"]').should('be.visible')
      
      // Track import stages
      cy.get('[data-cy="current-stage"]').should('contain', 'Importing customers')
      cy.get('[data-cy="progress-percentage"]', { timeout: 30000 }).should('contain', '25%')
      
      cy.get('[data-cy="current-stage"]').should('contain', 'Importing items')
      cy.get('[data-cy="progress-percentage"]', { timeout: 30000 }).should('contain', '50%')
      
      cy.get('[data-cy="current-stage"]').should('contain', 'Importing invoices')
      cy.get('[data-cy="progress-percentage"]', { timeout: 30000 }).should('contain', '75%')
      
      cy.get('[data-cy="current-stage"]').should('contain', 'Importing payments')
      cy.get('[data-cy="progress-percentage"]', { timeout: 30000 }).should('contain', '100%')
      
      // Verify completion
      cy.get('[data-cy="import-complete"]', { timeout: 60000 }).should('be.visible')
      cy.get('[data-cy="import-summary"]').should('contain', 'Migration completed successfully')
    })

    it('should display detailed import statistics', () => {
      cy.completeOnivaMigration()
      
      // Check import statistics
      cy.get('[data-cy="import-stats"]').within(() => {
        cy.get('[data-cy="customers-imported"]').should('contain', '14 of 15')
        cy.get('[data-cy="invoices-imported"]').should('contain', '43 of 45')
        cy.get('[data-cy="items-imported"]').should('contain', '120 of 120')
        cy.get('[data-cy="payments-imported"]').should('contain', '37 of 38')
      })
      
      // Check error details
      cy.get('[data-cy="import-errors"]').within(() => {
        cy.get('[data-cy="duplicate-customers"]').should('contain', '1 duplicate')
        cy.get('[data-cy="invalid-invoices"]').should('contain', '2 validation errors')
        cy.get('[data-cy="failed-payments"]').should('contain', '1 missing reference')
      })
      
      // Verify Macedonia-specific validation
      cy.get('[data-cy="macedonia-validation"]').within(() => {
        cy.get('[data-cy="vat-ids-validated"]').should('contain', '14 valid VAT IDs')
        cy.get('[data-cy="tax-rates-mapped"]').should('contain', '3 tax rates configured')
        cy.get('[data-cy="currency-converted"]').should('contain', 'All amounts in MKD')
      })
    })

    it('should handle import errors with rollback capability', () => {
      cy.startMegasoftMigration()
      
      // Simulate import error during process
      cy.intercept('POST', '/api/migration/import', {
        statusCode: 422,
        body: {
          error: 'Database constraint violation',
          details: 'Duplicate VAT ID found',
          rollback_available: true
        }
      }).as('importError')
      
      cy.get('[data-cy="start-import"]').click()
      cy.wait('@importError')
      
      // Verify error handling
      cy.get('[data-cy="import-error"]').should('be.visible')
      cy.get('[data-cy="error-message"]').should('contain', 'Database constraint violation')
      cy.get('[data-cy="error-details"]').should('contain', 'Duplicate VAT ID found')
      
      // Test rollback functionality
      cy.get('[data-cy="rollback-import"]').click()
      cy.get('[data-cy="confirm-rollback"]').click()
      
      cy.get('[data-cy="rollback-progress"]').should('be.visible')
      cy.get('[data-cy="rollback-complete"]', { timeout: 30000 }).should('contain', 'Rollback completed')
      
      // Verify system state is restored
      cy.visit('/admin/customers')
      cy.get('[data-cy="customers-table"]').should('not.contain', 'Imported Customer')
    })

    it('should pause and resume migration process', () => {
      cy.startLargeMigration() // Large dataset for testing pause/resume
      
      cy.get('[data-cy="start-import"]').click()
      
      // Wait for import to start
      cy.get('[data-cy="progress-percentage"]').should('contain', '10%')
      
      // Pause import
      cy.get('[data-cy="pause-import"]').click()
      cy.get('[data-cy="import-paused"]').should('be.visible')
      cy.get('[data-cy="pause-reason"]').type('Testing pause functionality')
      cy.get('[data-cy="confirm-pause"]').click()
      
      // Verify paused state
      cy.get('[data-cy="import-status"]').should('contain', 'Paused')
      cy.get('[data-cy="resume-import"]').should('be.visible')
      
      // Resume import
      cy.get('[data-cy="resume-import"]').click()
      cy.get('[data-cy="import-resumed"]').should('be.visible')
      
      // Verify import continues
      cy.get('[data-cy="progress-percentage"]', { timeout: 30000 }).should('contain', '20%')
    })
  })

  describe('Post-Migration Validation', () => {
    it('should validate imported customer data integrity', () => {
      cy.completeOnivaMigration()
      
      // Navigate to customers
      cy.visit('/admin/customers')
      
      // Verify imported customers
      cy.get('[data-cy="customers-table"]').should('contain', 'Македонска Трговска')
      cy.get('[data-cy="customers-table"]').should('contain', 'MK4080003501234')
      
      // Check customer details
      cy.get('[data-cy="customers-table"]').contains('Македонска Трговска').click()
      
      // Verify all fields imported correctly
      cy.get('[data-cy="customer-name"]').should('contain', 'Македонска Трговска ДОО')
      cy.get('[data-cy="customer-email"]').should('contain', '@kompanija.mk')
      cy.get('[data-cy="customer-tax-id"]').should('contain', 'MK4080003501234')
      cy.get('[data-cy="customer-address"]').should('contain', 'Скопје')
      cy.get('[data-cy="customer-address"]').should('contain', '1000')
    })

    it('should validate imported invoice data and relationships', () => {
      cy.completeOnivaMigration()
      
      cy.visit('/admin/invoices')
      
      // Verify imported invoices
      cy.get('[data-cy="invoices-table"]').should('contain', 'ОНИ-2024-001')
      
      // Check invoice details
      cy.get('[data-cy="invoices-table"]').contains('ОНИ-2024-001').click()
      
      // Verify invoice structure
      cy.get('[data-cy="invoice-customer"]').should('contain', 'Македонска Трговска')
      cy.get('[data-cy="invoice-total"]').should('contain', 'MKD')
      cy.get('[data-cy="invoice-items"]').should('have.length.at.least', 1)
      
      // Check VAT calculations
      cy.get('[data-cy="tax-breakdown"]').should('contain', 'ДДВ 18%')
      cy.get('[data-cy="tax-amount"]').should('be.visible')
      
      // Verify relationships maintained
      cy.get('[data-cy="related-payments"]').should('be.visible')
    })

    it('should validate payment matching and reconciliation', () => {
      cy.completeOnivaMigration()
      
      cy.visit('/admin/payments')
      
      // Verify payments imported
      cy.get('[data-cy="payments-table"]').should('contain', 'ОНИ-ПЛ-001')
      
      // Check payment details
      cy.get('[data-cy="payments-table"]').contains('ОНИ-ПЛ-001').click()
      
      // Verify payment-invoice relationship
      cy.get('[data-cy="payment-invoice"]').should('contain', 'ОНИ-2024-001')
      cy.get('[data-cy="payment-amount"]').should('contain', 'MKD')
      cy.get('[data-cy="payment-method"]').should('be.visible')
      
      // Check payment status
      cy.get('[data-cy="payment-status"]').should('contain', 'Completed')
    })

    it('should generate migration completion report', () => {
      cy.completeOnivaMigration()
      
      // Generate migration report
      cy.visit('/admin/migration/report')
      
      // Verify report sections
      cy.get('[data-cy="migration-summary"]').should('be.visible')
      cy.get('[data-cy="data-statistics"]').should('be.visible')
      cy.get('[data-cy="error-analysis"]').should('be.visible')
      cy.get('[data-cy="performance-metrics"]').should('be.visible')
      
      // Check summary data
      cy.get('[data-cy="source-system"]').should('contain', 'Onivo')
      cy.get('[data-cy="migration-date"]').should('contain', new Date().toISOString().split('T')[0])
      cy.get('[data-cy="total-records"]').should('contain', '219') // 15+45+120+38+1
      cy.get('[data-cy="success-rate"]').should('contain', '97%')
      
      // Download report
      cy.get('[data-cy="download-report"]').click()
      cy.get('[data-cy="report-downloaded"]').should('be.visible')
    })
  })

  // Helper commands for migration testing
  Cypress.Commands.add('prepareMigrationTestFiles', () => {
    // This would prepare test migration files
    // In real implementation, this would create fixture files
    cy.log('Preparing migration test files')
  })

  Cypress.Commands.add('startMigrationWizard', (systemType) => {
    cy.visit('/admin/migration/wizard')
    cy.get(`[data-cy="system-${systemType}"]`).click()
    cy.get('[data-cy="next-step"]').click()
  })

  Cypress.Commands.add('uploadMigrationFiles', (files) => {
    files.forEach((filename) => {
      cy.get('[data-cy="file-upload"]').selectFile(`cypress/fixtures/migration/${filename}`)
    })
    cy.get('[data-cy="validate-files"]').click()
    cy.get('[data-cy="next-step"]').click()
  })

  Cypress.Commands.add('autoMapFields', () => {
    cy.get('[data-cy="auto-map-fields"]').click()
    cy.get('[data-cy="validate-mapping"]').click()
    cy.get('[data-cy="next-step"]').click()
  })

  Cypress.Commands.add('startCompleteOnivaMigration', () => {
    cy.startMigrationWizard('onivo')
    cy.uploadMigrationFiles([
      'onivo_customers.csv',
      'onivo_invoices.csv',
      'onivo_items.csv',
      'onivo_payments.csv'
    ])
    cy.autoMapFields()
  })

  Cypress.Commands.add('completeOnivaMigration', () => {
    cy.startCompleteOnivaMigration()
    cy.get('[data-cy="start-import"]').click()
    cy.get('[data-cy="import-complete"]', { timeout: 60000 }).should('be.visible')
  })

  Cypress.Commands.add('startMegasoftMigration', () => {
    cy.startMigrationWizard('megasoft')
    cy.uploadMigrationFiles(['megasoft_export.xml'])
    cy.autoMapFields()
  })

  Cypress.Commands.add('startLargeMigration', () => {
    cy.startMigrationWizard('csv')
    cy.uploadMigrationFiles(['large_dataset.csv'])
    cy.autoMapFields()
  })

  Cypress.Commands.add('mockLargeFileUpload', (filename, sizeMB) => {
    // Mock large file upload for testing
    cy.window().then((win) => {
      const mockFile = new File(['test'.repeat(sizeMB * 1024 * 256)], filename, {
        type: 'text/csv'
      })
      
      const dataTransfer = new DataTransfer()
      dataTransfer.items.add(mockFile)
      
      cy.get('[data-cy="file-upload"]').then($input => {
        $input[0].files = dataTransfer.files
        $input[0].dispatchEvent(new Event('change', { bubbles: true }))
      })
    })
  })

  afterEach(() => {
    // Clean up migration test data
    cy.window().then((win) => {
      // Clean up any imported test data
      // This would typically involve API calls to remove test records
    })
  })
})


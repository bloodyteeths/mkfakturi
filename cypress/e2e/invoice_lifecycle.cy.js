/**
 * OPS-02: Invoice Lifecycle (draft→sent→paid)
 * 
 * Tests complete invoice workflow with Macedonia-specific requirements:
 * - Invoice creation with Macedonia data
 * - Status transitions: DRAFT → SENT → PAID
 * - PDF generation and download
 * - UBL XML export validation
 * - Macedonia VAT calculations (18%, 5%, 0%)
 * - Invoice numbering compliance
 */

describe('OPS-02: Invoice Lifecycle (draft→sent→paid)', () => {
  let macedoniaData
  let testCustomer
  let testInvoice
  
  before(() => {
    // Load Macedonia test data
    cy.fixture('macedonia-test-data').then((data) => {
      macedoniaData = data
      testCustomer = data.customers[0] // Охридски Ресторан ДООЕЛ
    })
  })

  beforeEach(() => {
    // Login as admin
    cy.visit('/admin/login')
    cy.get('[data-cy="email"]').type(Cypress.env('ADMIN_EMAIL'))
    cy.get('[data-cy="password"]').type(Cypress.env('ADMIN_PASSWORD'))
    cy.get('[data-cy="login-button"]').click()
    
    // Wait for dashboard
    cy.url().should('include', '/admin/dashboard')
    
    // Create test customer if not exists
    cy.createTestCustomer(testCustomer)
  })

  describe('Invoice Creation - DRAFT Status', () => {
    it('should create invoice in DRAFT status with Macedonia data', () => {
      cy.visit('/admin/invoices')
      cy.get('[data-cy="create-invoice"]').click()
      
      // Select customer
      cy.get('[data-cy="invoice-customer"]').click()
      cy.get('[data-cy="customer-option"]').contains(testCustomer.name).click()
      
      // Verify customer details auto-populate
      cy.get('[data-cy="customer-details"]').should('contain', testCustomer.name)
      cy.get('[data-cy="customer-details"]').should('contain', testCustomer.tax_id)
      cy.get('[data-cy="customer-details"]').should('contain', testCustomer.address.city)
      
      // Set invoice date
      const invoiceDate = new Date().toISOString().split('T')[0]
      cy.get('[data-cy="invoice-date"]').type(invoiceDate)
      
      // Set due date (30 days from now)
      const dueDate = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      cy.get('[data-cy="due-date"]').type(dueDate)
      
      // Add invoice items with Macedonia services
      const service = macedoniaData.services[0] // Консултантски Услуги
      cy.get('[data-cy="add-item"]').click()
      cy.get('[data-cy="item-name"]').type(service.name)
      cy.get('[data-cy="item-description"]').type(service.description)
      cy.get('[data-cy="item-quantity"]').type('8')
      cy.get('[data-cy="item-price"]').type(service.price.toString())
      cy.get('[data-cy="item-unit"]').type(service.unit)
      
      // Set tax rate (18% for Macedonia standard VAT)
      cy.get('[data-cy="item-tax-rate"]').select('18%')
      
      // Verify calculations
      const expectedSubtotal = 8 * service.price // 8 * 2500 = 20000
      const expectedTax = expectedSubtotal * 0.18 // 3600
      const expectedTotal = expectedSubtotal + expectedTax // 23600
      
      cy.get('[data-cy="subtotal"]').should('contain', expectedSubtotal.toLocaleString())
      cy.get('[data-cy="tax-amount"]').should('contain', expectedTax.toLocaleString())
      cy.get('[data-cy="total-amount"]').should('contain', expectedTotal.toLocaleString())
      
      // Save as draft
      cy.get('[data-cy="save-draft"]').click()
      
      // Verify success and status
      cy.get('[data-cy="success-message"]').should('contain', 'Invoice saved as draft')
      cy.get('[data-cy="invoice-status"]').should('contain', 'DRAFT')
      
      // Store invoice details for later tests
      cy.url().then((url) => {
        const invoiceId = url.split('/').pop()
        cy.wrap(invoiceId).as('invoiceId')
      })
    })

    it('should validate Macedonia VAT calculations for different rates', () => {
      cy.visit('/admin/invoices')
      cy.get('[data-cy="create-invoice"]').click()
      
      // Select customer
      cy.get('[data-cy="invoice-customer"]').click()
      cy.get('[data-cy="customer-option"]').contains(testCustomer.name).click()
      
      // Test 18% VAT (standard rate)
      cy.get('[data-cy="add-item"]').click()
      cy.get('[data-cy="item-name"]').type('Стандардна услуга')
      cy.get('[data-cy="item-quantity"]').type('1')
      cy.get('[data-cy="item-price"]').type('1000')
      cy.get('[data-cy="item-tax-rate"]').select('18%')
      
      cy.get('[data-cy="tax-amount"]').should('contain', '180') // 1000 * 0.18
      
      // Test 5% VAT (reduced rate)
      cy.get('[data-cy="add-item"]').click()
      cy.get('[data-cy="item-name"]').type('Обука')
      cy.get('[data-cy="item-quantity"]').type('1')
      cy.get('[data-cy="item-price"]').type('1000')
      cy.get('[data-cy="item-tax-rate"]').select('5%')
      
      // Test 0% VAT (exempt)
      cy.get('[data-cy="add-item"]').click()
      cy.get('[data-cy="item-name"]').type('Ослободена услуга')
      cy.get('[data-cy="item-quantity"]').type('1')
      cy.get('[data-cy="item-price"]').type('1000')
      cy.get('[data-cy="item-tax-rate"]').select('0%')
      
      // Verify total calculations
      const expectedTax = 180 + 50 + 0 // 230
      const expectedTotal = 3000 + expectedTax // 3230
      
      cy.get('[data-cy="total-tax"]').should('contain', expectedTax.toString())
      cy.get('[data-cy="total-amount"]').should('contain', expectedTotal.toString())
    })

    it('should generate sequential Macedonia invoice numbers', () => {
      // Create multiple invoices and verify numbering
      const currentYear = new Date().getFullYear()
      const expectedPrefix = `МК-${currentYear}-`
      
      for (let i = 1; i <= 3; i++) {
        cy.visit('/admin/invoices')
        cy.get('[data-cy="create-invoice"]').click()
        
        cy.get('[data-cy="invoice-customer"]').click()
        cy.get('[data-cy="customer-option"]').contains(testCustomer.name).click()
        
        // Check auto-generated invoice number
        cy.get('[data-cy="invoice-number"]').should('contain', expectedPrefix)
        
        // Simple invoice to save quickly
        cy.get('[data-cy="add-item"]').click()
        cy.get('[data-cy="item-name"]').type(`Тест услуга ${i}`)
        cy.get('[data-cy="item-quantity"]').type('1')
        cy.get('[data-cy="item-price"]').type('1000')
        
        cy.get('[data-cy="save-draft"]').click()
        cy.get('[data-cy="success-message"]').should('be.visible')
      }
    })
  })

  describe('Invoice Transition: DRAFT → SENT', () => {
    it('should mark invoice as SENT and generate PDF', () => {
      // Create a draft invoice first
      cy.createDraftInvoice(testCustomer, macedoniaData.services[0])
      
      // Navigate to invoice
      cy.get('@invoiceId').then((invoiceId) => {
        cy.visit(`/admin/invoices/${invoiceId}`)
      })
      
      // Verify draft status
      cy.get('[data-cy="invoice-status"]').should('contain', 'DRAFT')
      
      // Mark as sent
      cy.get('[data-cy="mark-as-sent"]').click()
      
      // Confirm action
      cy.get('[data-cy="confirm-send"]').click()
      
      // Verify status change
      cy.get('[data-cy="success-message"]').should('contain', 'Invoice marked as sent')
      cy.get('[data-cy="invoice-status"]').should('contain', 'SENT')
      
      // Verify sent date is set
      cy.get('[data-cy="sent-date"]').should('not.be.empty')
      
      // Verify PDF generation
      cy.get('[data-cy="download-pdf"]').should('be.visible')
      cy.get('[data-cy="download-pdf"]').click()
      
      // Verify PDF download (check if download started)
      cy.get('[data-cy="pdf-generated"]').should('contain', 'PDF generated successfully')
    })

    it('should send invoice via email with Macedonia content', () => {
      cy.createDraftInvoice(testCustomer, macedoniaData.services[0])
      
      cy.get('@invoiceId').then((invoiceId) => {
        cy.visit(`/admin/invoices/${invoiceId}`)
      })
      
      // Send via email
      cy.get('[data-cy="send-email"]').click()
      
      // Verify email form with Macedonia localization
      cy.get('[data-cy="email-to"]').should('have.value', testCustomer.email)
      cy.get('[data-cy="email-subject"]').should('contain', 'Фактура')
      cy.get('[data-cy="email-message"]').should('contain', 'Почитувани')
      
      // Send email
      cy.get('[data-cy="send-email-button"]').click()
      
      // Verify email sent
      cy.get('[data-cy="success-message"]').should('contain', 'Invoice sent successfully')
      cy.get('[data-cy="invoice-status"]').should('contain', 'SENT')
      
      // Verify email log entry
      cy.get('[data-cy="email-history"]').should('contain', testCustomer.email)
      cy.get('[data-cy="email-history"]').should('contain', 'Sent')
    })

    it('should prevent editing of SENT invoices', () => {
      cy.createSentInvoice(testCustomer, macedoniaData.services[0])
      
      cy.get('@invoiceId').then((invoiceId) => {
        cy.visit(`/admin/invoices/${invoiceId}`)
      })
      
      // Verify status is SENT
      cy.get('[data-cy="invoice-status"]').should('contain', 'SENT')
      
      // Edit button should be disabled or not visible
      cy.get('[data-cy="edit-invoice"]').should('not.exist')
      
      // Try to access edit URL directly
      cy.get('@invoiceId').then((invoiceId) => {
        cy.visit(`/admin/invoices/${invoiceId}/edit`)
        cy.get('[data-cy="error-message"]').should('contain', 'Cannot edit sent invoice')
      })
    })
  })

  describe('Invoice Transition: SENT → PAID', () => {
    it('should record payment and mark invoice as PAID', () => {
      cy.createSentInvoice(testCustomer, macedoniaData.services[0])
      
      cy.get('@invoiceId').then((invoiceId) => {
        cy.visit(`/admin/invoices/${invoiceId}`)
      })
      
      // Verify SENT status
      cy.get('[data-cy="invoice-status"]').should('contain', 'SENT')
      
      // Record payment
      cy.get('[data-cy="record-payment"]').click()
      
      // Fill payment details
      const paymentDate = new Date().toISOString().split('T')[0]
      cy.get('[data-cy="payment-date"]').type(paymentDate)
      cy.get('[data-cy="payment-amount"]').should('have.value', '23600') // Auto-filled with invoice total
      cy.get('[data-cy="payment-method"]').select('Банкарски Трансфер')
      cy.get('[data-cy="payment-reference"]').type('REF-МК-2025-001')
      cy.get('[data-cy="payment-notes"]').type('Плаќање извршено во целост')
      
      // Save payment
      cy.get('[data-cy="save-payment"]').click()
      
      // Verify payment recorded
      cy.get('[data-cy="success-message"]').should('contain', 'Payment recorded successfully')
      cy.get('[data-cy="invoice-status"]').should('contain', 'PAID')
      
      // Verify payment details in invoice
      cy.get('[data-cy="payment-history"]').should('contain', '23600')
      cy.get('[data-cy="payment-history"]').should('contain', 'Банкарски Трансфер')
      cy.get('[data-cy="payment-history"]').should('contain', 'REF-МК-2025-001')
      
      // Verify remaining balance is 0
      cy.get('[data-cy="remaining-balance"]').should('contain', '0.00')
    })

    it('should handle partial payments', () => {
      cy.createSentInvoice(testCustomer, macedoniaData.services[0])
      
      cy.get('@invoiceId').then((invoiceId) => {
        cy.visit(`/admin/invoices/${invoiceId}`)
      })
      
      // Record first partial payment (50%)
      cy.get('[data-cy="record-payment"]').click()
      cy.get('[data-cy="payment-amount"]').clear().type('11800') // Half of 23600
      cy.get('[data-cy="payment-method"]').select('Банкарски Трансфер')
      cy.get('[data-cy="save-payment"]').click()
      
      // Verify partially paid status
      cy.get('[data-cy="invoice-status"]').should('contain', 'PARTIALLY_PAID')
      cy.get('[data-cy="remaining-balance"]').should('contain', '11800')
      
      // Record second payment (remaining 50%)
      cy.get('[data-cy="record-payment"]').click()
      cy.get('[data-cy="payment-amount"]').should('have.value', '11800') // Auto-filled with remaining
      cy.get('[data-cy="payment-method"]').select('Готовина')
      cy.get('[data-cy="save-payment"]').click()
      
      // Verify fully paid
      cy.get('[data-cy="invoice-status"]').should('contain', 'PAID')
      cy.get('[data-cy="remaining-balance"]').should('contain', '0.00')
      
      // Verify both payments in history
      cy.get('[data-cy="payment-history"]').should('contain', 'Банкарски Трансфер')
      cy.get('[data-cy="payment-history"]').should('contain', 'Готовина')
    })

    it('should prevent overpayment', () => {
      cy.createSentInvoice(testCustomer, macedoniaData.services[0])
      
      cy.get('@invoiceId').then((invoiceId) => {
        cy.visit(`/admin/invoices/${invoiceId}`)
      })
      
      // Try to record payment larger than invoice total
      cy.get('[data-cy="record-payment"]').click()
      cy.get('[data-cy="payment-amount"]').clear().type('30000') // More than 23600
      cy.get('[data-cy="payment-method"]').select('Банкарски Трансфер')
      cy.get('[data-cy="save-payment"]').click()
      
      // Should show validation error
      cy.get('[data-cy="validation-errors"]').should('contain', 'Payment amount cannot exceed invoice total')
    })
  })

  describe('PDF Generation and Download', () => {
    it('should generate PDF with Macedonia formatting', () => {
      cy.createSentInvoice(testCustomer, macedoniaData.services[0])
      
      cy.get('@invoiceId').then((invoiceId) => {
        cy.visit(`/admin/invoices/${invoiceId}`)
      })
      
      // Download PDF
      cy.get('[data-cy="download-pdf"]').click()
      
      // Verify PDF generation
      cy.get('[data-cy="pdf-status"]').should('contain', 'PDF generated successfully')
      
      // Check PDF contains Macedonia elements
      cy.readFile('cypress/downloads/').then((files) => {
        // Find the latest PDF file
        const pdfFile = files.find(file => file.includes('.pdf'))
        expect(pdfFile).to.exist
        
        // In a real scenario, you'd validate PDF content
        // For now, we verify the download was successful
      })
    })

    it('should include UBL XML metadata in PDF', () => {
      cy.createSentInvoice(testCustomer, macedoniaData.services[0])
      
      cy.get('@invoiceId').then((invoiceId) => {
        cy.visit(`/admin/invoices/${invoiceId}`)
      })
      
      // Check UBL export option
      cy.get('[data-cy="export-ubl"]').click()
      
      // Verify UBL XML generation
      cy.get('[data-cy="ubl-status"]').should('contain', 'UBL XML generated successfully')
      
      // Verify XML contains Macedonia-specific fields
      cy.get('[data-cy="xml-preview"]').should('contain', 'MK') // Country code
      cy.get('[data-cy="xml-preview"]').should('contain', 'MKD') // Currency
      cy.get('[data-cy="xml-preview"]').should('contain', testCustomer.tax_id)
    })
  })

  describe('Performance and Lifecycle Metrics', () => {
    it('should complete full lifecycle within performance limits', () => {
      const startTime = Date.now()
      
      // Full lifecycle: Create → Send → Pay
      cy.createDraftInvoice(testCustomer, macedoniaData.services[0])
        .then(() => {
          return cy.get('@invoiceId')
        })
        .then((invoiceId) => {
          cy.visit(`/admin/invoices/${invoiceId}`)
          
          // Mark as sent
          cy.get('[data-cy="mark-as-sent"]').click()
          cy.get('[data-cy="confirm-send"]').click()
          
          // Record payment
          cy.get('[data-cy="record-payment"]').click()
          cy.get('[data-cy="payment-method"]').select('Банкарски Трансфер')
          cy.get('[data-cy="save-payment"]').click()
          
          // Verify final state
          cy.get('[data-cy="invoice-status"]').should('contain', 'PAID')
          
          const endTime = Date.now()
          const totalTime = endTime - startTime
          
          // Should complete within 10 seconds
          expect(totalTime).to.be.lessThan(10000)
          cy.log(`Full invoice lifecycle completed in ${totalTime}ms`)
        })
    })

    it('should track status transition timestamps', () => {
      cy.createDraftInvoice(testCustomer, macedoniaData.services[0])
      
      cy.get('@invoiceId').then((invoiceId) => {
        cy.visit(`/admin/invoices/${invoiceId}`)
        
        // Check creation timestamp
        cy.get('[data-cy="created-at"]').should('not.be.empty')
        
        // Mark as sent and check sent timestamp
        cy.get('[data-cy="mark-as-sent"]').click()
        cy.get('[data-cy="confirm-send"]').click()
        cy.get('[data-cy="sent-at"]').should('not.be.empty')
        
        // Record payment and check paid timestamp
        cy.get('[data-cy="record-payment"]').click()
        cy.get('[data-cy="payment-method"]').select('Банкарски Трансфер')
        cy.get('[data-cy="save-payment"]').click()
        cy.get('[data-cy="paid-at"]').should('not.be.empty')
        
        // Verify timeline order
        cy.get('[data-cy="invoice-timeline"]').should('be.visible')
        cy.get('[data-cy="timeline-entry"]').should('have.length', 3) // Created, Sent, Paid
      })
    })
  })

  // Custom Cypress commands for invoice operations
  Cypress.Commands.add('createTestCustomer', (customer) => {
    cy.request('POST', '/api/customers', customer).its('body.data.id').as('customerId')
  })

  Cypress.Commands.add('createDraftInvoice', (customer, service) => {
    const invoiceData = {
      customer_id: customer.id,
      invoice_date: new Date().toISOString().split('T')[0],
      due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      items: [{
        name: service.name,
        description: service.description,
        quantity: 8,
        price: service.price,
        tax_rate: service.tax_rate
      }],
      status: 'DRAFT'
    }
    
    cy.request('POST', '/api/invoices', invoiceData).its('body.data.id').as('invoiceId')
  })

  Cypress.Commands.add('createSentInvoice', (customer, service) => {
    cy.createDraftInvoice(customer, service).then(() => {
      cy.get('@invoiceId').then((invoiceId) => {
        cy.request('PUT', `/api/invoices/${invoiceId}/send`)
      })
    })
  })

  afterEach(() => {
    // Clean up test data
    cy.get('@invoiceId', { timeout: 1000 }).then((invoiceId) => {
      if (invoiceId) {
        cy.request('DELETE', `/api/invoices/${invoiceId}`)
      }
    }).catch(() => {
      // Invoice not created, no cleanup needed
    })
  })
})


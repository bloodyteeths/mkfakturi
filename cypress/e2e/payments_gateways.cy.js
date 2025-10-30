/**
 * OPS-03: Payment Processing (Paddle, CPAY, manual)
 * 
 * Tests all payment gateway integrations with Macedonia-specific scenarios:
 * - Paddle payment gateway (international cards)
 * - CPAY payment gateway (Macedonia local)
 * - Manual payment recording
 * - Webhook processing and validation
 * - Payment method validation
 * - Multi-currency support (MKD primary)
 */

describe('OPS-03: Payment Processing (Paddle, CPAY, manual)', () => {
  let macedoniaData
  let testInvoice
  let testCustomer
  
  before(() => {
    // Load Macedonia test data
    cy.fixture('macedonia-test-data').then((data) => {
      macedoniaData = data
      testCustomer = data.customers[0]
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
    
    // Create test invoice for payment testing
    cy.createTestInvoice(testCustomer, macedoniaData.services[0]).then((invoiceId) => {
      testInvoice = { id: invoiceId, total: 23600 } // 8 * 2500 * 1.18
    })
  })

  describe('Manual Payment Recording', () => {
    it('should record manual payment with Macedonia bank transfer', () => {
      cy.visit(`/admin/invoices/${testInvoice.id}`)
      
      // Record payment
      cy.get('[data-cy="record-payment"]').click()
      
      // Fill payment details
      const paymentDate = new Date().toISOString().split('T')[0]
      cy.get('[data-cy="payment-date"]').type(paymentDate)
      cy.get('[data-cy="payment-amount"]').should('have.value', testInvoice.total.toString())
      
      // Select Macedonia bank transfer method
      cy.get('[data-cy="payment-method"]').select('Банкарски Трансфер')
      cy.get('[data-cy="payment-reference"]').type('REF-STOPANSKA-2025-001')
      cy.get('[data-cy="payment-notes"]').type('Плаќање преку Стопанска Банка')
      
      // Additional Macedonia-specific fields
      cy.get('[data-cy="bank-name"]').type('Стопанска Банка АД')
      cy.get('[data-cy="bank-account"]').type('300000000012345')
      cy.get('[data-cy="transaction-id"]').type('TXN-МК-2025-001')
      
      // Save payment
      cy.get('[data-cy="save-payment"]').click()
      
      // Verify payment recorded
      cy.get('[data-cy="success-message"]').should('contain', 'Payment recorded successfully')
      cy.get('[data-cy="invoice-status"]').should('contain', 'PAID')
      
      // Verify payment details
      cy.get('[data-cy="payment-history"]').should('contain', '23,600')
      cy.get('[data-cy="payment-history"]').should('contain', 'Банкарски Трансфер')
      cy.get('[data-cy="payment-history"]').should('contain', 'REF-STOPANSKA-2025-001')
      cy.get('[data-cy="payment-history"]').should('contain', 'Стопанска Банка АД')
    })

    it('should record cash payment with MKD currency', () => {
      cy.visit(`/admin/invoices/${testInvoice.id}`)
      
      cy.get('[data-cy="record-payment"]').click()
      
      // Cash payment details
      const paymentDate = new Date().toISOString().split('T')[0]
      cy.get('[data-cy="payment-date"]').type(paymentDate)
      cy.get('[data-cy="payment-amount"]').clear().type('23600')
      cy.get('[data-cy="payment-method"]').select('Готовина')
      cy.get('[data-cy="currency"]').should('have.value', 'MKD') // Default currency
      cy.get('[data-cy="payment-notes"]').type('Плаќање во готовина во канцеларија')
      
      // Cash-specific fields
      cy.get('[data-cy="cash-received"]').type('24000') // Received more than due
      cy.get('[data-cy="change-given"]').should('have.value', '400') // Auto-calculated
      
      cy.get('[data-cy="save-payment"]').click()
      
      // Verify cash payment recorded
      cy.get('[data-cy="success-message"]').should('contain', 'Cash payment recorded')
      cy.get('[data-cy="invoice-status"]').should('contain', 'PAID')
      cy.get('[data-cy="payment-history"]').should('contain', 'Готовина')
      cy.get('[data-cy="payment-history"]').should('contain', 'Change: 400 MKD')
    })

    it('should validate payment amount and currency', () => {
      cy.visit(`/admin/invoices/${testInvoice.id}`)
      
      cy.get('[data-cy="record-payment"]').click()
      
      // Test invalid payment amounts
      const invalidAmounts = ['-1000', '0', '50000'] // Negative, zero, exceeds invoice
      
      invalidAmounts.forEach((amount) => {
        cy.get('[data-cy="payment-amount"]').clear().type(amount)
        cy.get('[data-cy="payment-method"]').select('Готовина')
        cy.get('[data-cy="save-payment"]').click()
        
        if (amount === '-1000' || amount === '0') {
          cy.get('[data-cy="validation-errors"]').should('contain', 'Payment amount must be positive')
        } else if (amount === '50000') {
          cy.get('[data-cy="validation-errors"]').should('contain', 'Payment amount cannot exceed invoice total')
        }
        
        cy.get('[data-cy="payment-amount"]').clear()
      })
    })
  })

  describe('CPAY Payment Gateway Integration', () => {
    it('should initiate CPAY payment for Macedonia cards', () => {
      // Visit payment page
      cy.visit(`/admin/invoices/${testInvoice.id}/payment`)
      
      // Select CPAY gateway
      cy.get('[data-cy="payment-gateway"]').select('CPAY')
      
      // Verify CPAY is available for MKD
      cy.get('[data-cy="gateway-info"]').should('contain', 'CPAY - Macedonia Local Gateway')
      cy.get('[data-cy="supported-cards"]').should('contain', 'Visa')
      cy.get('[data-cy="supported-cards"]').should('contain', 'MasterCard')
      cy.get('[data-cy="supported-cards"]').should('contain', 'Diners')
      
      // Fill payment form
      cy.get('[data-cy="cardholder-name"]').type('Петар Петровски')
      cy.get('[data-cy="card-number"]').type('4111111111111111') // Test Visa
      cy.get('[data-cy="expiry-month"]').select('12')
      cy.get('[data-cy="expiry-year"]').select('2026')
      cy.get('[data-cy="cvv"]').type('123')
      
      // CPAY specific fields
      cy.get('[data-cy="billing-address"]').type('ул. Македонија 15')
      cy.get('[data-cy="billing-city"]').type('Скопје')
      cy.get('[data-cy="billing-zip"]').type('1000')
      cy.get('[data-cy="billing-country"]').select('Macedonia')
      
      // Verify amount in MKD
      cy.get('[data-cy="payment-amount"]').should('contain', '23,600 MKD')
      
      // Submit payment (in test mode)
      cy.get('[data-cy="pay-now"]').click()
      
      // Should redirect to CPAY test environment
      cy.url().should('include', 'cpay.com.mk')
      
      // Simulate successful payment (this would be manual in real testing)
      cy.mockCpaySuccess()
      
      // Verify return to application
      cy.url().should('include', '/admin/invoices')
      cy.get('[data-cy="payment-success"]').should('contain', 'Payment processed successfully')
    })

    it('should handle CPAY webhook validation', () => {
      // Mock incoming CPAY webhook
      const webhookData = {
        transaction_id: 'CPAY-TXN-123456',
        amount: 23600,
        currency: 'MKD',
        status: 'SUCCESS',
        invoice_id: testInvoice.id,
        payment_method: 'VISA',
        card_last_four: '1111',
        timestamp: new Date().toISOString()
      }
      
      // Send webhook to application
      cy.request({
        method: 'POST',
        url: '/api/webhooks/cpay',
        body: webhookData,
        headers: {
          'X-CPAY-Signature': 'test_signature_hash'
        }
      }).then((response) => {
        expect(response.status).to.equal(200)
        expect(response.body.message).to.equal('Webhook processed successfully')
      })
      
      // Verify payment was recorded
      cy.visit(`/admin/invoices/${testInvoice.id}`)
      cy.get('[data-cy="invoice-status"]').should('contain', 'PAID')
      cy.get('[data-cy="payment-history"]').should('contain', 'CPAY-TXN-123456')
      cy.get('[data-cy="payment-history"]').should('contain', 'VISA ending in 1111')
    })

    it('should handle CPAY payment failures', () => {
      cy.visit(`/admin/invoices/${testInvoice.id}/payment`)
      
      cy.get('[data-cy="payment-gateway"]').select('CPAY')
      
      // Fill with invalid card (test failure scenario)
      cy.get('[data-cy="cardholder-name"]').type('DECLINE CARD')
      cy.get('[data-cy="card-number"]').type('4000000000000002') // Declined test card
      cy.get('[data-cy="expiry-month"]').select('12')
      cy.get('[data-cy="expiry-year"]').select('2026')
      cy.get('[data-cy="cvv"]').type('123')
      
      cy.get('[data-cy="pay-now"]').click()
      
      // Should show error from CPAY
      cy.get('[data-cy="payment-error"]').should('contain', 'Payment declined')
      cy.get('[data-cy="error-details"]').should('contain', 'Insufficient funds')
      
      // Invoice should remain unpaid
      cy.visit(`/admin/invoices/${testInvoice.id}`)
      cy.get('[data-cy="invoice-status"]').should('contain', 'SENT')
    })
  })

  describe('Paddle Payment Gateway Integration', () => {
    it('should initiate Paddle payment for international processing', () => {
      cy.visit(`/admin/invoices/${testInvoice.id}/payment`)
      
      // Select Paddle gateway
      cy.get('[data-cy="payment-gateway"]').select('Paddle')
      
      // Verify Paddle is available
      cy.get('[data-cy="gateway-info"]').should('contain', 'Paddle - International Gateway')
      cy.get('[data-cy="supported-currencies"]').should('contain', 'EUR')
      cy.get('[data-cy="supported-currencies"]').should('contain', 'USD')
      
      // Paddle should show currency conversion
      cy.get('[data-cy="original-amount"]').should('contain', '23,600 MKD')
      cy.get('[data-cy="converted-amount"]').should('contain', 'EUR') // Auto-converted
      
      // Paddle opens in overlay/iframe
      cy.get('[data-cy="pay-with-paddle"]').click()
      
      // Verify Paddle checkout loads
      cy.get('[data-cy="paddle-checkout"]').should('be.visible')
      cy.get('[data-cy="paddle-amount"]').should('be.visible')
      
      // Simulate successful Paddle payment
      cy.mockPaddleSuccess()
      
      // Verify success handling
      cy.get('[data-cy="payment-success"]').should('contain', 'Payment processed via Paddle')
    })

    it('should handle Paddle webhook processing', () => {
      const paddleWebhook = {
        alert_id: 'PADDLE-ALERT-789',
        alert_name: 'payment_succeeded',
        checkout_id: 'CHK-PADDLE-456',
        currency: 'EUR',
        earnings: 385.25, // ~23600 MKD converted to EUR
        gross_revenue: 385.25,
        invoice_id: testInvoice.id,
        payment_method: 'card',
        product_id: 'prod_invoice_payment',
        status: 'completed'
      }
      
      cy.request({
        method: 'POST',
        url: '/api/webhooks/paddle',
        body: paddleWebhook,
        headers: {
          'X-Paddle-Signature': 'test_paddle_signature'
        }
      }).then((response) => {
        expect(response.status).to.equal(200)
      })
      
      // Verify payment recorded with conversion
      cy.visit(`/admin/invoices/${testInvoice.id}`)
      cy.get('[data-cy="invoice-status"]').should('contain', 'PAID')
      cy.get('[data-cy="payment-history"]').should('contain', 'Paddle')
      cy.get('[data-cy="payment-history"]').should('contain', '385.25 EUR')
      cy.get('[data-cy="payment-history"]').should('contain', '(23,600 MKD)')
    })
  })

  describe('Payment Gateway Configuration', () => {
    it('should configure CPAY gateway settings', () => {
      cy.visit('/admin/settings/payment-gateways')
      
      // Configure CPAY
      cy.get('[data-cy="cpay-settings"]').click()
      
      cy.get('[data-cy="cpay-enabled"]').check()
      cy.get('[data-cy="cpay-mode"]').select('test') // Use test mode
      cy.get('[data-cy="cpay-merchant-id"]').type('TEST_MERCHANT_MK')
      cy.get('[data-cy="cpay-api-key"]').type('test_api_key_cpay')
      cy.get('[data-cy="cpay-webhook-secret"]').type('test_webhook_secret')
      
      // Macedonia-specific settings
      cy.get('[data-cy="cpay-default-currency"]').select('MKD')
      cy.get('[data-cy="cpay-language"]').select('mk') // Macedonia language
      
      // Save settings
      cy.get('[data-cy="save-cpay-settings"]').click()
      cy.get('[data-cy="success-message"]').should('contain', 'CPAY settings saved')
      
      // Test connection
      cy.get('[data-cy="test-cpay-connection"]').click()
      cy.get('[data-cy="connection-status"]').should('contain', 'CPAY connection successful')
    })

    it('should configure Paddle gateway settings', () => {
      cy.visit('/admin/settings/payment-gateways')
      
      // Configure Paddle
      cy.get('[data-cy="paddle-settings"]').click()
      
      cy.get('[data-cy="paddle-enabled"]').check()
      cy.get('[data-cy="paddle-mode"]').select('sandbox')
      cy.get('[data-cy="paddle-vendor-id"]').type('TEST_VENDOR_123')
      cy.get('[data-cy="paddle-api-key"]').type('test_paddle_api_key')
      cy.get('[data-cy="paddle-public-key"]').type('test_paddle_public_key')
      
      // Currency settings
      cy.get('[data-cy="paddle-currencies"]').select(['EUR', 'USD', 'GBP'])
      cy.get('[data-cy="paddle-default-currency"]').select('EUR')
      
      // Save settings
      cy.get('[data-cy="save-paddle-settings"]').click()
      cy.get('[data-cy="success-message"]').should('contain', 'Paddle settings saved')
      
      // Test connection
      cy.get('[data-cy="test-paddle-connection"]').click()
      cy.get('[data-cy="connection-status"]').should('contain', 'Paddle connection successful')
    })

    it('should validate payment gateway priorities', () => {
      cy.visit('/admin/settings/payment-gateways')
      
      // Set gateway priority for Macedonia
      cy.get('[data-cy="gateway-priorities"]').within(() => {
        // For MKD currency, prefer CPAY
        cy.get('[data-cy="mkd-primary"]').select('CPAY')
        cy.get('[data-cy="mkd-secondary"]').select('Manual')
        cy.get('[data-cy="mkd-tertiary"]').select('Paddle')
        
        // For other currencies, prefer Paddle
        cy.get('[data-cy="other-primary"]').select('Paddle')
        cy.get('[data-cy="other-secondary"]').select('Manual')
      })
      
      cy.get('[data-cy="save-priorities"]').click()
      
      // Verify priorities are applied
      cy.visit(`/admin/invoices/${testInvoice.id}/payment`)
      cy.get('[data-cy="recommended-gateway"]').should('contain', 'CPAY')
      cy.get('[data-cy="gateway-reason"]').should('contain', 'Recommended for MKD payments')
    })
  })

  describe('Payment Reporting and Analytics', () => {
    it('should generate payment method analytics', () => {
      // Create multiple payments with different methods
      const paymentMethods = ['CPAY', 'Paddle', 'Банкарски Трансфер', 'Готовина']
      
      // Create test payments
      paymentMethods.forEach((method, index) => {
        cy.createTestPayment(testInvoice.id, {
          method: method,
          amount: 5000 + (index * 1000),
          currency: 'MKD'
        })
      })
      
      // Check payment analytics
      cy.visit('/admin/reports/payments')
      
      // Verify payment breakdown by method
      cy.get('[data-cy="payment-methods-chart"]').should('be.visible')
      cy.get('[data-cy="cpay-percentage"]').should('be.visible')
      cy.get('[data-cy="paddle-percentage"]').should('be.visible')
      cy.get('[data-cy="manual-percentage"]').should('be.visible')
      
      // Check currency breakdown
      cy.get('[data-cy="currency-breakdown"]').should('contain', 'MKD')
      cy.get('[data-cy="total-mkd"]').should('be.visible')
    })

    it('should track payment success rates by gateway', () => {
      cy.visit('/admin/reports/payment-gateways')
      
      // Check gateway performance metrics
      cy.get('[data-cy="cpay-success-rate"]').should('be.visible')
      cy.get('[data-cy="paddle-success-rate"]').should('be.visible')
      
      // Check average processing times
      cy.get('[data-cy="cpay-avg-time"]').should('contain', 'ms')
      cy.get('[data-cy="paddle-avg-time"]').should('contain', 'ms')
      
      // Check failure reasons
      cy.get('[data-cy="failure-analysis"]').should('be.visible')
      cy.get('[data-cy="common-failures"]').should('contain', 'Declined')
    })
  })

  // Helper commands for payment testing
  Cypress.Commands.add('createTestInvoice', (customer, service) => {
    const invoiceData = {
      customer_id: customer.id,
      items: [{
        name: service.name,
        quantity: 8,
        price: service.price,
        tax_rate: service.tax_rate
      }],
      status: 'SENT'
    }
    
    return cy.request('POST', '/api/invoices', invoiceData).its('body.data.id')
  })

  Cypress.Commands.add('createTestPayment', (invoiceId, paymentData) => {
    return cy.request('POST', `/api/invoices/${invoiceId}/payments`, paymentData)
  })

  Cypress.Commands.add('mockCpaySuccess', () => {
    // Mock successful CPAY return
    cy.window().then((win) => {
      win.dispatchEvent(new CustomEvent('cpay-success', {
        detail: {
          transaction_id: 'CPAY-TEST-SUCCESS',
          amount: 23600,
          currency: 'MKD'
        }
      }))
    })
  })

  Cypress.Commands.add('mockPaddleSuccess', () => {
    // Mock successful Paddle return
    cy.window().then((win) => {
      win.dispatchEvent(new CustomEvent('paddle-success', {
        detail: {
          checkout_id: 'PADDLE-TEST-SUCCESS',
          amount: 385.25,
          currency: 'EUR'
        }
      }))
    })
  })

  afterEach(() => {
    // Clean up test payments and invoices
    if (testInvoice && testInvoice.id) {
      cy.request('DELETE', `/api/invoices/${testInvoice.id}`)
    }
  })
})


describe('Accounts Payable UI', () => {
  beforeEach(() => {
    cy.clearCookies()
    cy.clearLocalStorage()
    cy.loginAsAdmin()
  })

  it('navigates to Suppliers and creates a supplier', () => {
    cy.visit('/admin/suppliers')
    cy.contains('Suppliers').should('be.visible')

    cy.contains('New Supplier').click()

    const name = `Test Supplier ${Date.now()}`
    cy.get('input').contains('Name')
    cy.get('input[name="name"], input').first().type(name)

    cy.get('button[type="submit"]').click()

    cy.contains('Supplier created successfully').should('be.visible')
  })

  it('navigates to Bills index', () => {
    cy.visit('/admin/bills')
    cy.contains('Bills').should('be.visible')
  })

  it('opens Bills Inbox', () => {
    cy.visit('/admin/bills/inbox')
    cy.contains('Bills Inbox').should('be.visible')
  })

  it('opens Receipt Scanner page', () => {
    cy.visit('/admin/receipts/scan')
    cy.contains('Scan Fiscal Receipt').should('be.visible')
  })
})


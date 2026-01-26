describe('Partner Accounting Reports', () => {
  beforeEach(() => {
    cy.loginAsAdmin()
  })

  it('loads the general ledger page', () => {
    cy.visit('/admin/partner/accounting/general-ledger')
    // Wait for page to load
    cy.get('body').should('be.visible')
    // Check for page title or company selector
    cy.contains('Главна книга', { timeout: 10000 }).should('be.visible')
  })

  it('loads the trial balance page', () => {
    cy.visit('/admin/partner/accounting/trial-balance')
    cy.get('body').should('be.visible')
    cy.contains('Пробен биланс', { timeout: 10000 }).should('be.visible')
  })
})

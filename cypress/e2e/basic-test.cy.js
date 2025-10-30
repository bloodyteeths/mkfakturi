/**
 * Basic Cypress Test - Configuration Validation
 * Simple test to verify Cypress setup is working
 */

describe('Basic Cypress Configuration Test', () => {
  it('should validate Cypress is properly configured (no server required)', () => {
    // Test basic Cypress functionality
    cy.log('Cypress is configured and working')
    
    // Test environment variables
    expect(Cypress.env('ADMIN_EMAIL')).to.equal('admin@invoiceshelf.com')
    expect(Cypress.env('ADMIN_PASSWORD')).to.equal('password')
    
    // Test viewport
    cy.viewport(1280, 720)
    
    // Test fixture loading
    cy.fixture('macedonia-test-data').then((data) => {
      expect(data).to.have.property('companies')
      expect(data).to.have.property('customers')
      expect(data.companies).to.be.an('array')
      expect(data.companies.length).to.be.greaterThan(0)
    })
  })

  it('should have Macedonia-specific test data', () => {
    cy.fixture('macedonia-test-data').then((data) => {
      // Test Macedonia company data
      const mkCompany = data.companies[0]
      expect(mkCompany.name).to.include('Македонска')
      expect(mkCompany.tax_id).to.match(/^MK\d{13}$/)
      expect(mkCompany.address.city).to.equal('Скопје')
      expect(mkCompany.address.country).to.equal('Macedonia')
      
      // Test Macedonia customer data
      const mkCustomer = data.customers[0]
      expect(mkCustomer.name).to.include('Охридски')
      expect(mkCustomer.email).to.include('.mk')
      expect(mkCustomer.address.city).to.equal('Охрид')
      
      // Test Macedonia services
      const mkService = data.services[0]
      expect(mkService.name).to.include('Консултантски')
      expect(mkService.tax_rate).to.be.oneOf([18, 5, 0])
    })
  })
})


# Partner Bureau Acceptance Checklist
## Manual Validation for Pilot Production Approval

**Version**: 1.0  
**Date**: 2025-07-26  
**Target**: Partner Bureau Validation for Pilot Customers  
**Platform**: FACTURINO v1 - Macedonia Accounting Platform

---

## Overview

This comprehensive checklist enables partner bureaus to systematically validate all platform capabilities before approving pilot customer deployment. Each validation step focuses on business workflows and compliance requirements critical for Macedonia accounting operations.

**Validation Approach**: Business-focused testing from partner bureau perspective, not technical QA testing.

---

## Section A: Platform Access & Authentication

### A1. Partner Console Access
- [ ] **A1.1** - Access partner console at `/console` URL with partner credentials
- [ ] **A1.2** - Verify console displays all assigned client companies with correct names and addresses
- [ ] **A1.3** - Confirm commission rates are displayed correctly for each client
- [ ] **A1.4** - Validate primary company indicators (badges) show for designated primary clients
- [ ] **A1.5** - Test company switching functionality works seamlessly between different clients

### A2. Multi-Client Management
- [ ] **A2.1** - Switch between client companies and verify context changes (company name in header)
- [ ] **A2.2** - Confirm data isolation - Client A's invoices not visible when viewing Client B
- [ ] **A2.3** - Test accessing different client databases maintains separate customer lists
- [ ] **A2.4** - Verify commission tracking works independently for each client company
- [ ] **A2.5** - Validate session persistence maintains client context across browser refresh

---

## Section B: Macedonia Localization Validation

### B1. Macedonian Language Interface
- [ ] **B1.1** - Navigate entire platform and confirm 95%+ UI elements display in Macedonian Cyrillic
- [ ] **B1.2** - Verify dashboard, navigation menus, and form labels use proper Macedonian terminology
- [ ] **B1.3** - Test invoice creation form uses correct Macedonia business terms ("Фактура", "Купувач", etc.)
- [ ] **B1.4** - Confirm date pickers and number formats follow Macedonia conventions (dd.mm.yyyy)
- [ ] **B1.5** - Validate error messages and notifications appear in Macedonian

### B2. Albanian Language Support
- [ ] **B2.1** - Switch language to Albanian and verify core UI elements translate correctly
- [ ] **B2.2** - Test invoice creation and customer management in Albanian interface
- [ ] **B2.3** - Confirm Albanian language maintains proper business terminology
- [ ] **B2.4** - Validate language switching preserves user data and context

### B3. PDF and Email Localization
- [ ] **B3.1** - Generate invoice PDF and verify headers, labels in Macedonian
- [ ] **B3.2** - Create invoice PDF in Albanian and confirm proper language rendering
- [ ] **B3.3** - Send test invoice email and verify email content uses Macedonian text
- [ ] **B3.4** - Test PDF download filenames use proper Macedonia naming conventions

---

## Section C: Invoice Management & UBL XML Compliance

### C1. Invoice Creation Workflow
- [ ] **C1.1** - Create standard invoice with Macedonia VAT rates (18% standard, 5% reduced)
- [ ] **C1.2** - Add multiple line items with different VAT rates and verify calculations
- [ ] **C1.3** - Include customer with Macedonia address, EDB number, and contact details
- [ ] **C1.4** - Test invoice numbering follows Macedonia business document standards
- [ ] **C1.5** - Verify invoice total calculations include proper VAT amounts in MKD

### C2. UBL XML Export & Digital Signatures
- [ ] **C2.1** - Export invoice as UBL 2.1 XML and verify file downloads correctly
- [ ] **C2.2** - Open UBL XML file and confirm Macedonia-specific elements (MK country code, MKD currency)
- [ ] **C2.3** - Test digital signature functionality with test certificate
- [ ] **C2.4** - Verify signed UBL XML contains embedded certificate and signature
- [ ] **C2.5** - Upload QES certificate via certificate management interface

### C3. E-faktura Integration
- [ ] **C3.1** - Generate UBL XML for Macedonia tax authority portal
- [ ] **C3.2** - Test XML validation against Macedonia e-faktura requirements
- [ ] **C3.3** - Upload test XML to staging portal and verify acceptance
- [ ] **C3.4** - Confirm digital signature verification works with partner bureau certificates
- [ ] **C3.5** - Validate automatic portal upload functionality for production use

---

## Section D: Banking Integration (PSD2)

### D1. Bank Connection Setup
- [ ] **D1.1** - Configure Stopanska Bank connection with sandbox credentials
- [ ] **D1.2** - Test OAuth authentication flow with Stopanska sandbox environment
- [ ] **D1.3** - Verify token generation and renewal process works automatically
- [ ] **D1.4** - Confirm bank account details sync correctly from PSD2 API
- [ ] **D1.5** - Test rate limiting compliance (15 requests/minute maximum)

### D2. Transaction Synchronization
- [ ] **D2.1** - Execute manual bank sync and verify transactions import correctly
- [ ] **D2.2** - Confirm transaction descriptions appear in Macedonia language
- [ ] **D2.3** - Test automatic duplicate detection prevents transaction re-import
- [ ] **D2.4** - Verify bank balance updates correctly after transaction sync
- [ ] **D2.5** - Validate transaction categorization and reference number handling

### D3. Payment Matching System
- [ ] **D3.1** - Create unpaid invoice then import matching bank transaction
- [ ] **D3.2** - Verify automatic payment matching creates payment record
- [ ] **D3.3** - Test manual payment matching for ambiguous transactions
- [ ] **D3.4** - Confirm invoice status updates to "PAID" after successful matching
- [ ] **D3.5** - Validate confidence scoring system for payment matching accuracy

---

## Section E: Tax Compliance & VAT Returns

### E1. Macedonia VAT Management
- [ ] **E1.1** - Verify VAT tax types include 18% standard and 5% reduced rates
- [ ] **E1.2** - Test VAT calculations on invoices with multiple tax rates
- [ ] **E1.3** - Create VAT-exempt invoice and confirm 0% rate application
- [ ] **E1.4** - Validate VAT amounts display correctly in MKD currency
- [ ] **E1.5** - Confirm VAT number validation for business customers

### E2. ДДВ-04 VAT Return Generation
- [ ] **E2.1** - Generate ДДВ-04 XML for test period with multiple invoices
- [ ] **E2.2** - Verify XML validates against Macedonia tax authority schema
- [ ] **E2.3** - Test VAT return includes all relevant business transactions
- [ ] **E2.4** - Confirm calculations match manual VAT return preparation
- [ ] **E2.5** - Validate XML format ready for tax authority submission

### E3. Reporting and Compliance
- [ ] **E3.1** - Generate tax report for specific period and verify accuracy
- [ ] **E3.2** - Test invoice audit trail maintains 7-year compliance requirement
- [ ] **E3.3** - Verify digital signature audit trail for compliance tracking
- [ ] **E3.4** - Confirm data retention policies meet Macedonia legal requirements
- [ ] **E3.5** - Test GDPR-compliant data export and removal capabilities

---

## Section F: Customer & Data Migration

### F1. Universal Migration Wizard
- [ ] **F1.1** - Upload competitor export file (CSV/Excel) from Onivo/Megasoft
- [ ] **F1.2** - Verify automatic field mapping detects Macedonia business terms correctly
- [ ] **F1.3** - Test manual field mapping adjustments for custom formats
- [ ] **F1.4** - Confirm data validation identifies and reports errors clearly
- [ ] **F1.5** - Execute complete migration and verify all business data imports correctly

### F2. Migration Data Integrity
- [ ] **F2.1** - Validate customer relationships preserve (invoices linked to correct customers)
- [ ] **F2.2** - Test invoice line items maintain proper product and tax associations
- [ ] **F2.3** - Confirm payment records link correctly to invoices after migration
- [ ] **F2.4** - Verify currency conversions (EUR to MKD) calculate accurately
- [ ] **F2.5** - Test rollback capability restores system to pre-migration state

### F3. Competitor Integration
- [ ] **F3.1** - Test OnivoFetcher automated export download from competitor
- [ ] **F3.2** - Verify Macedonian language field detection in competitor exports
- [ ] **F3.3** - Confirm migration handles competitor-specific data formats
- [ ] **F3.4** - Test batch processing for large datasets (500+ customers)
- [ ] **F3.5** - Validate migration complete within acceptable timeframes (<10 minutes)

---

## Section G: Accountant System Integrations

### G1. MiniMax Integration
- [ ] **G1.1** - Configure MiniMax API credentials and test connection
- [ ] **G1.2** - Push test invoice to MiniMax and verify successful sync
- [ ] **G1.3** - Confirm invoice data maintains integrity in MiniMax format
- [ ] **G1.4** - Test error handling for failed MiniMax synchronization
- [ ] **G1.5** - Verify token management and renewal for continuous operation

### G2. PANTHEON eSlog Export
- [ ] **G2.1** - Configure PANTHEON eSlog export parameters
- [ ] **G2.2** - Generate nightly eSlog export and verify XML file creation
- [ ] **G2.3** - Validate eSlog XML format meets PANTHEON requirements
- [ ] **G2.4** - Test automated scheduling for regular export generation
- [ ] **G2.5** - Confirm export includes all relevant business transactions

### G3. CPay Payment Processing
- [ ] **G3.1** - Test CPay integration with Macedonia banking system
- [ ] **G3.2** - Process test payment and verify transaction completion
- [ ] **G3.3** - Confirm Macedonia Denar (MKD) currency support
- [ ] **G3.4** - Test payment authentication with SHA256 signatures
- [ ] **G3.5** - Validate integration with all major Macedonia banks

---

## Section H: Performance & Reliability

### H1. System Performance
- [ ] **H1.1** - Test dashboard loading time under 300ms with cached data
- [ ] **H1.2** - Verify invoice generation completes within 2 seconds
- [ ] **H1.3** - Test large file migration (1000+ records) completes successfully
- [ ] **H1.4** - Confirm concurrent user access maintains performance
- [ ] **H1.5** - Validate system stability during peak usage scenarios

### H2. Data Security & Backup
- [ ] **H2.1** - Verify all sensitive data encrypted in transit (HTTPS)
- [ ] **H2.2** - Test database backup and restore procedures
- [ ] **H2.3** - Confirm user access controls prevent unauthorized data access
- [ ] **H2.4** - Validate audit logs capture all critical business operations
- [ ] **H2.5** - Test disaster recovery procedures and data integrity

### H3. Production Deployment
- [ ] **H3.1** - Verify Docker production stack deploys correctly
- [ ] **H3.2** - Test HTTPS certificate generation and automatic renewal
- [ ] **H3.3** - Confirm health monitoring endpoints respond correctly
- [ ] **H3.4** - Validate resource limits prevent system overload
- [ ] **H3.5** - Test production backup and monitoring systems

---

## Section I: Business Workflow Validation

### I1. Complete Client Onboarding
- [ ] **I1.1** - Add new client company with full Macedonia business details
- [ ] **I1.2** - Configure client-specific settings (invoice templates, VAT rates)
- [ ] **I1.3** - Import client's customer database via migration wizard
- [ ] **I1.4** - Set up client's bank connections and test transaction sync
- [ ] **I1.5** - Generate first invoice and complete end-to-end payment cycle

### I2. Monthly Accounting Workflow
- [ ] **I2.1** - Process monthly bank statement reconciliation
- [ ] **I2.2** - Generate monthly VAT return and export ДДВ-04 XML
- [ ] **I2.3** - Create customer invoices for recurring services
- [ ] **I2.4** - Export financial reports for client review
- [ ] **I2.5** - Submit e-faktura uploads to tax authority

### I3. Year-End Procedures
- [ ] **I3.1** - Generate annual financial summaries for all clients
- [ ] **I3.2** - Export complete audit trail for 7-year retention
- [ ] **I3.3** - Verify all digital signatures remain valid for archived documents
- [ ] **I3.4** - Test data migration to new fiscal year structure
- [ ] **I3.5** - Confirm compliance with Macedonia accounting law requirements

---

## Validation Results Summary

### Critical Success Criteria (Must Pass)
- [ ] All Macedonia localization elements display correctly
- [ ] UBL XML export generates valid e-faktura documents
- [ ] Bank integration syncs transactions from major Macedonia banks
- [ ] Migration wizard successfully imports competitor data
- [ ] Accountant console enables seamless multi-client management
- [ ] ДДВ-04 VAT return generation meets tax authority requirements

### Business Value Indicators (Should Pass)
- [ ] Partner bureau can manage 10+ client companies efficiently
- [ ] Complete client migration completes within 10 minutes
- [ ] All client-facing documents properly localized
- [ ] Tax compliance features demonstrate competitive advantage
- [ ] Certificate management enables bureau autonomy
- [ ] System performance meets professional standards

### Partner Bureau Approval
- [ ] **Technical Validation**: All critical success criteria passed
- [ ] **Business Validation**: Partner bureau confirms realistic pilot workflow completion
- [ ] **Compliance Validation**: Macedonia tax and banking requirements satisfied
- [ ] **Localization Validation**: All client-facing materials properly localized
- [ ] **Integration Validation**: Competitor migration and accountant systems working

**Final Approval**: [ ] Partner bureau approves platform for pilot customer deployment

---

## Notes & Recommendations

### Implementation Priority
1. **Section C (Invoice/UBL)** - Core business value delivery
2. **Section B (Localization)** - Client-facing requirements
3. **Section F (Migration)** - Competitive differentiation
4. **Section D (Banking)** - Operational efficiency
5. **Sections A,E,G,H,I** - Professional capabilities

### Success Metrics
- **≥95%** platform functionality localized in Macedonian
- **≥20** sandbox transactions successfully imported from each bank
- **≥1** complete competitor business migration executed
- **≥5** client companies managed simultaneously
- **≥100%** ДДВ-04 XML validation compliance

### Post-Approval Actions
1. Partner bureau receives staging environment access
2. AccountantQuickStart.md documentation provided
3. Pilot customer onboarding initiated
4. Production deployment scheduled
5. Ongoing support and monitoring established

---

**Document Prepared By**: FACTURINO Development Team  
**Validation Target**: Macedonia Partner Bureau Pilot Approval  
**Platform Version**: v1.0.0-pilot  
**Last Updated**: 2025-07-26


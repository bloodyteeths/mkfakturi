# Facturino Macedonia - Accountant Quick-Start Guide

**Professional Accounting Platform for Partner Bureau Onboarding**

Version 1.0 | Created: July 26, 2025 | Target: Partner Bureau Sign-off

---

## 🎯 Executive Summary

Facturino Macedonia is the **only comprehensive accounting platform** designed specifically for Macedonian businesses and their accountant partners. This guide enables partner accounting bureaus to complete pilot testing and client onboarding with confidence.

### **Unique Competitive Advantages**
- ✅ **Universal Migration Wizard**: Import complete business data from ANY competitor in minutes
- ✅ **Multi-Client Console**: Manage unlimited companies from single professional interface  
- ✅ **Tax Compliance Automation**: ДДВ-04 VAT return generation with one click
- ✅ **Real Banking Integration**: Automatic transaction sync from all major Macedonia banks
- ✅ **E-faktura Ready**: UBL XML export with digital signatures for tax authority

---

## 📋 Table of Contents

1. [Initial Setup & Partner Account Creation](#1-initial-setup--partner-account-creation)
2. [Multi-Client Company Management](#2-multi-client-company-management)  
3. [Universal Migration Wizard](#3-universal-migration-wizard)
4. [Tax Compliance Features](#4-tax-compliance-features)
5. [Banking Integration & Transaction Sync](#5-banking-integration--transaction-sync)
6. [Troubleshooting & Support](#6-troubleshooting--support)
7. [Production Pilot Checklist](#7-production-pilot-checklist)

---

## 1. Initial Setup & Partner Account Creation

### **Step 1.1: Partner Bureau Registration**

**Prerequisites:**
- ЕДБ (Единствен даночен број) - Macedonia tax ID
- ЕМБС registration number  
- Valid email address for account management
- Macedonia business address

**Registration Process:**
```
1. Navigate to: https://facturino.mk/partner/register
2. Complete partner application form:
   - Bureau name and ЕДБ number
   - Contact information and address
   - Commission preferences (default: 15%)
   - Services offered checklist
3. Upload required documentation:
   - Business registration certificate
   - Tax authority registration
   - Professional liability insurance
4. Await approval (typically 24-48 hours)
```

**Screenshot Placeholder:**
*[Partner registration form with Macedonia-specific fields]*

### **Step 1.2: Account Activation & Login**

**After Approval:**
```
1. Receive activation email with temporary credentials
2. Login at: https://facturino.mk/partner/login
3. Complete security setup:
   - Change default password (minimum 12 characters)
   - Enable two-factor authentication
   - Configure recovery options
4. Review partner agreement and commission structure
```

**Initial Dashboard Overview:**
- Company management console
- Commission tracking dashboard  
- Migration wizard access
- Support contact information

---

## 2. Multi-Client Company Management

### **Step 2.1: Adding Client Companies**

**New Client Onboarding Process:**
```
1. From Partner Console → "Add New Client"
2. Enter client business information:
   - Company name and ЕДБ number
   - Address and contact details
   - Industry classification
   - Expected transaction volume
3. Set client-specific preferences:
   - Commission rate (if different from default)
   - Access permissions
   - Billing preferences
4. Generate client invitation link
5. Client completes their account setup
```

**Client Data Requirements:**
- ✅ Company registration details
- ✅ VAT number (if applicable)  
- ✅ Bank account information
- ✅ Authorized signatories
- ✅ Preferred invoice templates

### **Step 2.2: Multi-Client Console Navigation**

**Professional Interface Features:**

**Company Switcher:**
```html
<!-- Top navigation bar includes: -->
📊 [Current Company: Client Name] ▼
   🏢 ACME Ltd. (Primary) - 15% commission
   🏭 Textile Co. - 12.5% commission  
   🏬 Restaurant ABC - 15% commission
   ➕ Add New Client
   ⚙️ Manage Console
```

**Dashboard Overview:**
- **Company Cards**: Visual overview of all managed clients
- **Quick Actions**: Invoice creation, payment entry, report generation
- **Activity Feed**: Recent transactions across all clients
- **Commission Summary**: Real-time earnings tracking

### **Step 2.3: Context Switching & Permissions**

**Seamless Company Switching:**
```
1. Click company name in top navigation
2. Select target client from dropdown
3. Interface automatically updates to client context:
   - Client branding and preferences
   - Company-specific data and settings
   - Appropriate access permissions
4. All actions now apply to selected client
```

**Permission Levels:**
- 👁️ **View Only**: Reports and data access
- ✏️ **Standard**: Create/edit invoices, customers, items
- 🔧 **Advanced**: Settings, user management, integrations
- 🛡️ **Full Admin**: All features including sensitive data

---

## 3. Universal Migration Wizard

### **Step 3.1: Migration Overview**

**Supported Source Systems:**
- 🥇 **Onivo** (Market leader - fully automated)
- 🥈 **Megasoft** (Professional accounting)
- 🥉 **Pantheon** (Enterprise solution)
- 📄 **Excel/CSV** (Manual exports)
- 🌐 **Generic XML/UBL** (Standard formats)

**Migration Scope:**
- 👥 **Complete Customer Database** (with contact history)
- 🧾 **All Invoice Records** (including line items and taxes)
- 📦 **Product/Service Catalog** (with pricing and VAT rates)
- 💰 **Payment History** (matched to invoices)
- 💸 **Expense Records** (with categorization)
- 🔗 **Preserved Relationships** (customer payments, invoice items)

### **Step 3.2: Automated Competitor Migration**

**Onivo Migration (Recommended):**
```
1. Navigate to: Migration → "Import from Onivo"
2. Provide client's Onivo credentials:
   - Username and password
   - Company selection (if multiple)
3. Select data types to migrate:
   ☑ Customers and contacts
   ☑ Invoices (last 12 months)
   ☑ Items and services
   ☑ Payments and receipts
   ☑ Expense records
4. Click "Start Automated Migration"
5. Monitor progress (typically 5-15 minutes)
6. Review and approve imported data
```

**Automated Features:**
- 🤖 **Smart Field Mapping**: 95%+ accuracy for Macedonia business data
- 🔄 **Duplicate Detection**: Automatic handling of existing records
- 📊 **Data Validation**: Ensures compliance with Macedonia tax requirements
- 💱 **Currency Conversion**: EUR ↔ MKD with current exchange rates
- 📅 **Date Normalization**: Handles various date formats automatically

### **Step 3.3: Manual File Import**

**4-Step Import Wizard:**

**Step 1: File Upload**
```
1. Drag & drop or select files:
   - CSV (UTF-8 with Cyrillic support)
   - Excel (.xlsx, .xls)
   - XML (UBL, custom formats)
2. Configure parsing options:
   - Delimiter (comma, semicolon, tab)
   - Text encoding (UTF-8 recommended)
   - Header row detection
3. Preview first 5 rows for validation
```

**Step 2: Field Mapping**
```
1. Automatic field detection:
   - "naziv" → Customer Name
   - "embs" → Tax ID
   - "iznos" → Amount
   - "pdv_stapka" → VAT Rate
2. Manual mapping for undetected fields
3. Data transformation preview:
   - Date format standardization
   - Currency conversion
   - VAT rate validation
```

**Step 3: Data Validation**
```
1. Comprehensive validation rules:
   - Macedonia VAT number format
   - Required field completeness
   - Numeric format validation
   - Relationship integrity
2. Error report with specific issues:
   - Missing required data
   - Invalid format warnings
   - Duplicate record alerts
3. Optional data correction interface
```

**Step 4: Import Execution**
```
1. Review import summary:
   - Records to be created
   - Records to be updated
   - Records to be skipped
2. Select import options:
   - Skip duplicates vs. update existing
   - Error handling preferences
   - Backup original data
3. Execute import with progress tracking
4. Generate completion report with statistics
```

**Screenshot Placeholder:**
*[Migration wizard interface showing 4-step process with progress indicators]*

---

## 4. Tax Compliance Features

### **Step 4.1: Macedonia VAT Management**

**Supported VAT Rates:**
- 📊 **18% Standard Rate**: Most goods and services
- 📉 **5% Reduced Rate**: Essential goods, medications
- ⭕ **0% Zero Rate**: Exports, specific exemptions
- 🚫 **Exempt**: Financial services, education

**VAT Configuration:**
```
1. Navigate to: Settings → Tax Types
2. Verify Macedonia VAT rates:
   - "ДДВ 18%" (Standard)
   - "ДДВ 5%" (Reduced)
   - "ДДВ 0%" (Zero-rated)
3. Assign default rates to product categories
4. Configure tax-inclusive/exclusive pricing
```

### **Step 4.2: ДДВ-04 VAT Return Generation**

**Monthly VAT Return Process:**
```
1. Navigate to: Reports → Tax Returns → ДДВ-04
2. Select reporting period:
   - Month/quarter selection
   - Date range validation
   - Previous period comparison
3. Review VAT calculation summary:
   - Output VAT by rate (18%, 5%, 0%)
   - Input VAT (if expense tracking enabled)
   - Net VAT due or refund
4. Generate XML file:
   - Automatic compliance validation
   - Digital signature application
   - Portal-ready format
5. Download for submission to tax authority
```

**VAT Return Data Sources:**
- ✅ Paid invoices (automatic calculation)
- ✅ Tax-inclusive item pricing
- ✅ Multi-rate invoice support
- ✅ Customer VAT number validation
- ⚠️ Manual input VAT (expenses) - future enhancement

**Screenshot Placeholder:**
*[ДДВ-04 generation interface with Macedonia tax form preview]*

### **Step 4.3: E-faktura XML Export**

**UBL 2.1 Compliance Features:**
```
1. Navigate to: Invoice → [Select Invoice] → Export XML
2. Choose export options:
   - UBL 2.1 format (standard)
   - Digital signature application
   - Schema validation
3. Configure signing certificate:
   - Upload QES certificate (.p12/.pem)
   - Enter certificate password
   - Validate signing capability
4. Generate signed XML:
   - Macedonia-specific formatting
   - Cyrillic text support
   - Tax authority compliance
5. Upload to e-ujp.ujp.gov.mk portal
```

**Digital Signature Requirements:**
- 🔐 **QES Certificate**: Qualified Electronic Signature from authorized CA
- 🏢 **Company Registration**: Certificate must match business registration
- ⏰ **Validity Period**: Current, non-expired certificate
- 🔒 **Private Key**: Secure storage and password protection

---

## 5. Banking Integration & Transaction Sync

### **Step 5.1: PSD2 Bank Configuration**

**Supported Banks:**
- 🏦 **Стопанска Банка** (Market leader)
- 🏦 **НЛБ Банка** (NLB Group)
- 🏦 **Комерцијална Банка** (Commercial banking)

**Bank Account Setup:**
```
1. Navigate to: Settings → Banking → Add Account
2. Select bank provider from dropdown
3. Configure PSD2 connection:
   - Account number/IBAN
   - API authorization (OAuth2)
   - Permission scope selection
4. Test connection and sync settings:
   - Sync frequency (daily recommended)
   - Transaction history depth
   - Automatic matching preferences
```

### **Step 5.2: Automatic Transaction Sync**

**Daily Sync Process:**
```
1. Automated connection to bank PSD2 API
2. Fetch new transactions (rate limited - 15 req/min)
3. Transaction categorization:
   - Incoming payments (customer settlements)
   - Outgoing payments (supplier payments)
   - Bank fees and charges
   - Internal transfers
4. Automatic invoice matching:
   - Amount-based matching (±1% tolerance)
   - Reference number detection
   - Customer name matching
   - Date proximity scoring
5. Payment record creation for matched transactions
```

**Matching Algorithm:**
- 🎯 **Amount Matching**: 40% confidence weight
- 📅 **Date Proximity**: 30% confidence weight  
- 🔢 **Reference Numbers**: 30% confidence weight
- ✅ **Auto-Match Threshold**: 70% confidence minimum
- 👤 **Manual Review**: 50-70% confidence range

### **Step 5.3: Payment Reconciliation**

**Review & Approval Process:**
```
1. Navigate to: Banking → Unmatched Transactions
2. Review auto-match suggestions:
   - High confidence (≥70%): Auto-applied
   - Medium confidence (50-70%): Manual review
   - Low confidence (<50%): Manual matching
3. Manual matching interface:
   - Search unpaid invoices
   - Partial payment handling
   - Multiple invoice payments
4. Approve and create payment records
5. Update invoice status to "Paid"
```

**Screenshot Placeholder:**
*[Banking reconciliation interface showing transaction matching]*

---

## 6. Troubleshooting & Support

### **Step 6.1: Common Issues & Solutions**

**Migration Issues:**

*Problem: Low field mapping accuracy*
```
Solution:
1. Check source data column headers
2. Use Macedonian field names when possible:
   - "Назив" instead of "Name"
   - "ЕМБС" instead of "Tax ID"
3. Manual mapping for unrecognized fields
4. Contact support for custom mapping rules
```

*Problem: Import validation errors*
```
Solution:
1. Verify Macedonia business data format:
   - VAT numbers: MK + 11 digits
   - Tax IDs: ЕМБС format
   - Currency: MKD (denars)
2. Check required field completeness
3. Use provided error report for corrections
4. Import in smaller batches if memory issues occur
```

**Banking Sync Issues:**

*Problem: Bank connection failures*
```
Solution:
1. Verify PSD2 API credentials
2. Check account permissions and consent
3. Ensure firewall allows outbound HTTPS
4. Contact bank for API status updates
5. Use manual transaction import as backup
```

*Problem: Poor transaction matching*
```
Solution:
1. Ensure consistent invoice numbering
2. Include reference numbers in bank transfers
3. Train matching algorithm with manual corrections
4. Adjust matching threshold in settings
5. Use customer-specific payment references
```

### **Step 6.2: Performance Optimization**

**System Performance:**
- ⚡ **Response Times**: Target <300ms for standard operations
- 💾 **Caching**: Enabled for frequently accessed data
- 🔄 **Background Jobs**: Migration and sync operations
- 📊 **Database Optimization**: Indexed queries for large datasets

**Best Practices:**
```
1. Import Management:
   - Process large files during off-peak hours
   - Use chunked processing for 10,000+ records
   - Monitor import job status regularly
   
2. Banking Sync:
   - Schedule daily sync during business hours
   - Review unmatched transactions weekly
   - Maintain consistent payment references
   
3. Tax Compliance:
   - Generate VAT returns monthly
   - Validate XML before submission
   - Backup signed documents securely
```

### **Step 6.3: Support Channels**

**Partner Support:**
- 📧 **Email**: partners@facturino.mk
- 📞 **Phone**: +389 2 XXX-XXXX (Business hours)
- 💬 **Chat**: In-platform support widget
- 📚 **Documentation**: docs.facturino.mk/partners

**Technical Support:**
- 🔧 **System Issues**: tech@facturino.mk
- 🏦 **Banking Problems**: banking-support@facturino.mk
- 🧾 **Tax Compliance**: tax-support@facturino.mk
- 🚨 **Emergency**: +389 70 XXX-XXX (24/7)

**Business Development:**
- 🤝 **Partnership**: partnerships@facturino.mk
- 💼 **Sales**: sales@facturino.mk
- 📈 **Training**: training@facturino.mk

---

## 7. Production Pilot Checklist

### **Step 7.1: Technical Readiness**

**Infrastructure Validation:**
```
☑ Platform accessibility and login functionality
☑ Multi-client console operational
☑ Migration wizard tested with sample data
☑ Banking sync configured and tested
☑ Tax compliance features validated
☑ Digital certificate upload working
☑ Backup and recovery procedures tested
☑ Performance benchmarks met (<300ms response)
```

**Data Migration Validation:**
```
☑ Successful test migration from competitor system
☑ Field mapping accuracy >95% for Macedonia data
☑ Relationship integrity preserved (invoices→payments)
☑ Currency conversion accurate (EUR↔MKD)
☑ VAT calculations match source system
☑ Audit trail complete and accessible
☑ Rollback capability tested and confirmed
```

### **Step 7.2: Business Process Validation**

**Accountant Workflow Testing:**
```
☑ Partner registration and approval process
☑ Client onboarding and invitation system
☑ Company switching and context management
☑ Invoice creation and modification
☑ Payment processing and reconciliation
☑ Report generation and customization
☑ Tax return preparation and export
☑ Commission tracking and reporting
```

**Client Experience Testing:**
```
☑ Client portal access and navigation
☑ Invoice viewing and payment processing
☑ Document download functionality
☑ Email notifications working
☑ Multi-language support (Macedonian/Albanian)
☑ Mobile responsiveness verified
☑ Customer support accessibility
```

### **Step 7.3: Compliance & Security**

**Legal & Regulatory Compliance:**
```
☑ Macedonia VAT law compliance (18%, 5% rates)
☑ ДДВ-04 XML format validation
☑ UBL 2.1 standard compliance
☑ Digital signature requirements met
☑ Tax authority portal integration tested
☑ Data retention policies (7 years)
☑ GDPR compliance for EU business relations
☑ Business registration verification
```

**Security & Data Protection:**
```
☑ SSL/TLS encryption (A+ rating)
☑ Two-factor authentication enabled
☑ Regular security audits completed
☑ Data backup procedures verified
☑ Access control and permissions tested
☑ Audit logging comprehensive
☑ Incident response plan documented
☑ Penetration testing completed
```

### **Step 7.4: Support & Documentation**

**Partner Enablement:**
```
☑ Quick-start guide reviewed and approved
☑ Video tutorials accessible
☑ Technical documentation complete
☑ Support channels operational
☑ Training materials prepared
☑ FAQ database current
☑ Escalation procedures defined
☑ Performance monitoring active
```

### **Step 7.5: Go-Live Authorization**

**Final Approval Criteria:**
```
☑ All technical tests passed
☑ Business workflows validated
☑ Compliance requirements met
☑ Security audit completed
☑ Support infrastructure ready
☑ Pilot client identified and confirmed
☑ Success metrics defined
☑ Rollback plan documented
```

**Sign-off Requirements:**
- ✅ **Technical Lead**: System stability and performance
- ✅ **Business Analyst**: Workflow completeness and accuracy  
- ✅ **Compliance Officer**: Regulatory and legal requirements
- ✅ **Partner Bureau**: User acceptance and readiness
- ✅ **Project Manager**: Overall pilot readiness

---

## 🎯 Success Metrics

**Technical Metrics:**
- 🚀 **Migration Speed**: <10 minutes for SME complete business
- 🎯 **Field Mapping Accuracy**: >95% for Macedonia accounting data
- ⚡ **System Performance**: <300ms average response time
- 🔄 **Bank Sync Reliability**: >99% successful daily syncs
- ✅ **Data Integrity**: 100% relationship preservation

**Business Metrics:**
- 👥 **Client Satisfaction**: >90% user satisfaction score
- 📈 **Productivity Gain**: 50%+ reduction in manual data entry
- 💰 **Time to Value**: Client operational in <24 hours
- 🏆 **Competitive Advantage**: Only platform with universal migration
- 📊 **Partner Growth**: Foundation for rapid bureau expansion

---

## 📞 Next Steps

**For Partner Bureau:**
1. **Review**: Complete this guide review with technical team
2. **Test**: Execute pilot testing with non-critical client
3. **Validate**: Confirm all checklist items completed
4. **Approve**: Provide formal sign-off for production pilot
5. **Launch**: Begin client migration and onboarding

**For Facturino Team:**
1. **Support**: Provide hands-on assistance during pilot
2. **Monitor**: Track system performance and usage patterns
3. **Optimize**: Address any identified issues or improvements
4. **Expand**: Scale support for additional partner bureaus
5. **Enhance**: Implement feedback for platform improvements

---

**Document Information:**
- **Version**: 1.0
- **Last Updated**: July 26, 2025
- **Target Audience**: Partner Accounting Bureaus
- **Document Type**: Technical Implementation Guide
- **Approval Status**: Ready for Partner Review

**Contact Information:**
- **Project Lead**: development@facturino.mk
- **Technical Support**: support@facturino.mk
- **Partnership Development**: partners@facturino.mk

---

*This document establishes the foundation for successful partner bureau onboarding and pilot implementation. The comprehensive coverage of technical capabilities, business workflows, and support procedures ensures partner confidence in production deployment.*


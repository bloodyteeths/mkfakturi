# Macedonia Bank Transaction Sample Data

**Created for ROADMAP-FINAL.md Section A - SD-03**  
**Date: July 26, 2025**

## Overview

This directory contains realistic Macedonia bank transaction data in CSV format for Stopanska Bank and NLB Bank. The data includes authentic business transactions that correspond to the sample invoices from SD-01, providing a complete demo ecosystem for the platform.

## Files Structure

### Bank Transaction CSV Files
- `stopanska_bank_transactions.csv` - 15 transactions from Stopanska Bank (bank code 250)
- `nlb_bank_transactions.csv` - 15 transactions from NLB Bank (bank code 300)

## Macedonia Banking Compliance Features

### Bank Codes
- **Stopanska Bank**: Code 250 (authentic Macedonia bank code)
- **NLB Bank**: Code 300 (authentic Macedonia bank code)

### Transaction Types
- **Credit transactions**: Incoming payments from customers (invoice payments)
- **Debit transactions**: Outgoing payments (salaries, utilities, taxes, equipment)

### Account Format
- Authentic Macedonia account number format: `XXX-XXXXXXXXXX-XX`
- Proper IBAN format: `MK07XXXXXXXXXXXX`
- SWIFT codes: STBAMK22 (Stopanska), NLBMK22 (NLB)

### Currency
- All amounts in **MKD** (Macedonia Denar)
- Proper decimal formatting for Macedonia banking standards

## Business Scenarios Covered

### Customer Invoice Payments (Credits)
1. **МК-2024-001**: 53,100.00 MKD - Macedonian Bank IT services
2. **МК-2024-002**: 53,100.00 MKD - Stopanska Bank system integration
3. **МК-2024-005**: 29,500.00 MKD - Skopje Fair web development
4. **МК-2024-007**: 63,720.00 MKD - Macedonski Telekom software
5. **МК-2024-008**: 36,750.00 MKD - Medical practice system
6. **МК-2024-010**: 37,760.00 MKD - Bitola Fair e-commerce

### Business Expenses (Debits)
1. **Salary payments** - Monthly staff compensation
2. **Office rent** - Commercial space in Skopje
3. **Utilities** - Electricity, internet, phone
4. **Taxes** - VAT, corporate tax payments to УЈП
5. **Equipment** - Laptops, monitors, office supplies
6. **Bank fees** - Monthly banking costs
7. **Marketing** - Google Ads campaigns
8. **Professional services** - Legal, accounting, consulting

## Technical Implementation

### CSV Schema
```csv
transaction_date,booking_date,amount,currency,transaction_type,description,
debtor_name,debtor_account,creditor_name,creditor_account,payment_reference,
remittance_info,external_reference,transaction_id,booking_status
```

### Key Fields
- **transaction_date**: When transaction occurred
- **amount**: Transaction amount (positive for credit, as per CSV format)
- **transaction_type**: credit/debit classification
- **description**: Cyrillic description in Macedonian
- **payment_reference**: Reference to invoice payments
- **remittance_info**: Invoice number for automatic matching
- **external_reference**: Bank's transaction reference

## Import Script Usage

The bank transaction data can be imported using the provided script:

```bash
# Import bank transactions and create bank accounts
php tools/bank_import.php --company=1

# Import with force flag to skip confirmations
php tools/bank_import.php --company=1 --force

# Show help
php tools/bank_import.php --help
```

### Import Process
1. **Creates bank accounts**: Stopanska and NLB business accounts
2. **Imports transactions**: All CSV data into bank_transactions table
3. **Automatic matching**: Links transactions to existing invoices
4. **Comprehensive audit**: Provides detailed import statistics

## Success Criteria Validation

✅ **2 bank CSV files created**: Stopanska (15 transactions), NLB (15 transactions)  
✅ **Realistic Macedonia banking data**: Authentic bank codes, account formats, descriptions  
✅ **Invoice correlation**: Transactions match existing sample invoices for complete testing  
✅ **Business expense variety**: Comprehensive coverage of typical business costs  
✅ **Import script ready**: Complete tool for loading data into bank_transactions table  

## Database Integration

### Tables Created/Updated
- `bank_accounts`: Sample business accounts for both banks
- `bank_transactions`: All transaction records with proper relationships

### Automatic Features
- **Invoice matching**: Transactions with invoice references are auto-matched
- **Duplicate detection**: Prevents duplicate transaction imports
- **Audit trail**: Complete processing status and match confidence tracking

## Business Impact

This bank transaction data provides:
1. **Complete demo ecosystem** - Invoice + bank data for full platform demonstration
2. **F-13 Matcher testing** - Realistic data for automatic invoice matching validation
3. **PSD2 simulation** - Mimics real bank API data for integration testing
4. **Partner bureau confidence** - Shows complete banking integration capability
5. **Macedonia market credibility** - Authentic banking data formats and scenarios

## Future Enhancements

Consider adding:
- Additional Macedonia banks (Komercijalna, TTK, ProCredit)
- International transactions (EUR, USD)
- Recurring payment examples
- Standing order transactions
- Multi-currency scenarios
- Cross-border payments

---

**Contact Information**  
Generated by: Agent SD-03  
Date: July 26, 2025  
Project: FACTURINO Macedonia Accounting Platform  
Roadmap: ROADMAP-FINAL.md Section A


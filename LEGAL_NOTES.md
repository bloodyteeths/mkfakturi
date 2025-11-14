# Legal Notes - Facturino

## Open Source Compliance (AGPL-3.0)

### Base Project
Facturino is a fork of **InvoiceShelf**, an open-source invoicing platform licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)**.

**Original Project:**
- Name: InvoiceShelf
- Repository: https://github.com/InvoiceShelf/InvoiceShelf
- License: AGPL-3.0
- Copyright: © InvoiceShelf Contributors

### Our Modifications

Facturino adds the following Macedonian-localized features:

1. **Banking Integration (PSD2)**
   - Stopanska Banka, NLB, Komercijalna Banka connectors
   - OAuth2 authentication flows
   - MT940 transaction parsing
   - Automatic reconciliation engine

2. **Payment Gateways**
   - Paddle integration (international)
   - CPAY (CASYS) integration (Macedonia)
   - Webhook handlers for payment events

3. **E-Faktura / Electronic Invoicing**
   - UBL 2.1 XML generation
   - Qualified Electronic Signature (QES) support via xmlseclibs
   - Macedonian tax compliance

4. **Partner/Affiliate System**
   - Referral tracking and commission management
   - KYC verification workflow
   - Partner dashboard and reporting

5. **Accounting Backbone**
   - Double-entry ledger via eloquent-ifrs
   - Chart of accounts (Macedonian standards)
   - IFRS-compliant financial reports

6. **AI-Powered Tools**
   - Intelligent CSV import with field mapping
   - Multi-language support (Macedonian, Albanian, English)
   - Type detection and data normalization

7. **Multi-Tenancy Enhancements**
   - Row-Level Security (PostgreSQL RLS)
   - Company-scoped data isolation
   - Per-tenant feature flags

### Source Code Availability

As required by AGPL-3.0, all Facturino source code modifications are publicly available:

**Repository:** https://github.com/facturino/facturino (to be published)

**License:** AGPL-3.0 (same as upstream)

### Copyright Attribution

All original InvoiceShelf code retains its original copyright headers. Our modifications are marked with:

```php
// FACTURINO MODIFICATION: [description]
// Copyright © 2025 Facturino Contributors
// Licensed under AGPL-3.0
```

### Network Use Clause (AGPL § 13)

The AGPL license requires that users who interact with Facturino over a network must be able to access the source code. We comply by:

1. Linking to our public repository in the application footer
2. Providing a "View Source Code" link in the admin panel
3. Including this LEGAL_NOTES.md file in all distributions

## Third-Party Licenses

Facturino uses the following third-party packages:

### Core Dependencies

| Package | License | Purpose |
|---------|---------|---------|
| laravel/framework | MIT | PHP framework |
| laravel/sanctum | MIT | API authentication |
| laravel/cashier-paddle | MIT | Paddle billing integration |
| ekmungai/eloquent-ifrs | MIT | Accounting engine |
| num-num/ubl-invoice | MIT | UBL XML generation |
| robrichards/xmlseclibs | BSD-3-Clause | XML digital signatures |
| simplesoftwareio/simple-qrcode | MIT | QR code generation |
| spatie/laravel-backup | MIT | Backup automation |
| maatwebsite/excel | MIT | Excel import/export |
| league/csv | MIT | CSV parsing |
| jejik/mt940 | MIT | Bank statement parsing |

### Frontend Dependencies

| Package | License | Purpose |
|---------|---------|---------|
| vue | MIT | JavaScript framework |
| vuex | MIT | State management |
| tailwindcss | MIT | CSS framework |
| axios | MIT | HTTP client |

### Development Tools

| Package | License | Purpose |
|---------|---------|---------|
| laravel/telescope | MIT | Debugging |
| pestphp/pest | MIT | Testing framework |
| barryvdh/laravel-debugbar | MIT | Debug toolbar |

**All dependencies are compatible with AGPL-3.0 licensing.**

## Data Processing and Compliance

### GDPR Compliance

Facturino processes personal data in accordance with:
- **General Data Protection Regulation (GDPR)** - EU Regulation 2016/679
- **Law on Personal Data Protection** - Republic of North Macedonia

See our **Privacy Policy** for details: `/public/legal/privacy-policy.md`

### Data Processors

We use the following third-party data processors:

1. **Paddle** (Ireland) - Payment processing
   - DPA: https://www.paddle.com/legal/gdpr
   - SCCs in place for international transfers

2. **CPAY (CASYS)** (North Macedonia) - Domestic payment processing
   - DPA available upon request

3. **Railway** (USA) - Hosting infrastructure
   - DPA: https://railway.app/legal/data-processing-addendum
   - EU-US Data Privacy Framework certified

### Data Retention

- **Financial records:** 10 years (Macedonian tax law requirement)
- **Personal data:** Active subscription + 90 days
- **Backups:** 30 days rolling

## Trademark Notice

"Facturino" is a trademark of Facturino. "InvoiceShelf" is a trademark of the InvoiceShelf project.

## Disclaimer of Warranty

```
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
```

## Contact

For legal inquiries:
- **Email:** legal@facturino.mk
- **Privacy:** privacy@facturino.mk
- **Security:** security@facturino.mk

---

**Last Updated:** November 14, 2025

**Version:** 1.0.0

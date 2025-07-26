# Claude Rules – Facturino v1
(Last update 2025-07-25)

## 0 Mission
Build a Macedonian-localised fork of InvoiceShelf with bank-feed,
QES-signed e-Invoice, Paddle billing, CASYS pay-links and partner
commissions—**nothing else**.

---

## 1 Branch & PR
* **Branch** `ticket/<ID>-<slug>`
* **PR title** `[<ID>] <ticket title>`
* Push only when `php artisan test` & `npm run test` are green.

---

## 2 Dependency white-list (install nothing else)

### Core MVP packages
| Package | Ticket | Install cmd |
|---------|--------|-------------|
| laravel/cashier-paddle | B-31 series | composer require laravel/cashier-paddle |
| bojanvmk/laravel-cpay  | C-10 series | composer require bojanvmk/laravel-cpay |
| oak-labs-io/psd2       | F-10 series | composer require oak-labs-io/psd2 |
| num-num/ubl-invoice    | U-10        | composer require num-num/ubl-invoice |
| robrichards/xmlseclibs | U-12        | composer require robrichards/xmlseclibs |
| league/csv (already)   | I-11        | – |

### Competitive add-on packages
| Package | Ticket | Install cmd |
|---------|--------|-------------|
| media24si/eslog2 | PA-01 | composer require media24si/eslog2 |
| ekmungai/eloquent-ifrs | GL-01 | composer require ekmungai/eloquent-ifrs |
| brick/money | CF-01 | composer require brick/money |
| picqer/php-barcode-generator | INV-01 | composer require picqer/php-barcode-generator |
| simple-software-io/simple-qr-code | INV-01 | composer require simple-software-io/simple-qr-code |
| aws/aws-sdk-php | OCR-01 | composer require aws/aws-sdk-php |
| automattic/woocommerce-api-php | WOO-01 | composer require automattic/woocommerce-api-php |
| dhl-api/dhl-php-sdk | SHIP-01 | composer require dhl-api/dhl-php-sdk |
| keithbrink/affiliates-spark | AC-01 | composer require keithbrink/affiliates-spark |

**Any new lib** ⇒ open an `NX-??` ticket in Backlog; do NOT install.

---

## 3 Token efficiency
* Focus on ONE micro-ticket at a time
* Cache tool output; no duplicate searches
* Use code-search before asking language questions
* Exit early if blocked—create NX ticket

---

## 4 Checkpoint comments
After editing each file add `// CLAUDE-CHECKPOINT`.
Resume from the last checkpoint if interrupted.

---

## 5 File boundaries
* new PHP → `modules/Mk/**`
* new Vue → `resources/js/pages/partner/**`
* migrations under `database/migrations/2025_08_**.php`
* **NO edits** in `vendor/` or core models

---

## 6 Migrations & DB safety
* All schema tables created in **DB-00** only; later tickets are additive
* Every migration `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
  to avoid collation errors (errno 150)
* Foreign keys `ON DELETE RESTRICT`
* Test migrations with `php artisan migrate:fresh` before marking done

---

## 7 External API surprises
* **PSD2** rate-limit 15 req/min → sleep 60 s in cron
* **CASYS** UniqueID expires after 20 min
* **xmlsec** PFX chain errors—catch & log
* **Paddle** webhooks need CSRF exemption in VerifyCsrfToken

---

## 8 AGPL compliance
Keep upstream copyright headers;
link public fork in app footer; add `/LEGAL_NOTES.md`.

---

## 9 NX-ticket flow
Stuck on a ticket?  
1. create draft PR `[NX-##]`  
2. append row to Backlog in ROADMAP.md  
3. stop—wait for human review

---

## 10 Testing protocol
* Unit tests for all services
* Feature tests for all API endpoints
* Browser tests for critical UI flows
* Run full test suite before marking ticket done

---

## 11 Code conventions
* Follow InvoiceShelf's existing patterns
* PSR-12 for PHP
* Vue 3 Composition API
* Use existing UI components from InvoiceShelf
* No new CSS frameworks—use existing Tailwind setup

---

## 12 Documentation
* PHPDoc for all public methods
* README updates only when explicitly requested
* API documentation in `/docs/api/` if new endpoints added

---

# End rules
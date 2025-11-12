# Competitor Migration Guide

**Complete guide for migrating from popular accounting software to Facturino**

---

## Table of Contents

1. [Overview](#overview)
2. [Onivo Accounting](#onivo-accounting)
3. [Megasoft ERP](#megasoft-erp)
4. [Effect Plus](#effect-plus)
5. [Eurofaktura](#eurofaktura)
6. [Manager.io](#managerio)
7. [Field Mapping Tables](#field-mapping-tables)
8. [Migration Checklists](#migration-checklists)
9. [Common Issues](#common-issues)

---

## Overview

This guide provides step-by-step instructions for exporting data from popular accounting software and importing it into Facturino using the Migration Wizard.

### Migration Process Overview

```
┌───────────────────┐
│ 1. EXPORT DATA    │
│    from old       │
│    software       │
└─────────┬─────────┘
          ↓
┌───────────────────┐
│ 2. PREPARE FILE   │
│    (clean data,   │
│     check format) │
└─────────┬─────────┘
          ↓
┌───────────────────┐
│ 3. IMPORT TO      │
│    FACTURINO      │
│    via wizard     │
└─────────┬─────────┘
          ↓
┌───────────────────┐
│ 4. VERIFY DATA    │
│    and continue   │
│    business       │
└───────────────────┘
```

### Estimated Migration Time

| Software | Export Time | Data Prep | Import Time | Total |
|----------|-------------|-----------|-------------|-------|
| Onivo | 15-30 min | 10-20 min | 20-60 min | 45-110 min |
| Megasoft | 20-40 min | 15-30 min | 20-60 min | 55-130 min |
| Effect Plus | 15-30 min | 10-20 min | 20-60 min | 45-110 min |
| Eurofaktura | 20-40 min | 15-30 min | 20-60 min | 55-130 min |
| Manager.io | 10-20 min | 5-10 min | 20-60 min | 35-90 min |

*Times vary based on data volume. Estimates for ~1,000 records.*

---

## Onivo Accounting

### About Onivo

**Market Leader in Macedonia**
- Used by 40%+ of Macedonian businesses
- Cyrillic-first interface
- Strong local tax compliance
- Desktop and cloud versions

### Export Instructions

#### Step 1: Open Export Module

1. Launch Onivo software
2. Log in with your credentials
3. Navigate to **Извештаи** (Reports) menu
4. Select **Извоз на податоци** (Data Export)

#### Step 2: Export Customers

1. In Export dialog, select **Партнери** (Partners/Customers)
2. Choose date range: **Сите** (All) or specify period
3. Select format:
   - **CSV** (recommended) or
   - **Excel (.xlsx)**
4. Check these options:
   - ☑ Вклучи ЕДБ (Include Tax ID)
   - ☑ Вклучи контакт информации (Include contact info)
   - ☑ Вклучи адреса (Include address)
5. Click **Извези** (Export)
6. Save as: `onivo_customers.csv`

#### Step 3: Export Items

1. In Export dialog, select **Артикли** (Items/Products)
2. Choose: **Сите артикли** (All items)
3. Select format: **CSV** or **Excel**
4. Options:
   - ☑ Вклучи цени (Include prices)
   - ☑ Вклучи ДДВ стапки (Include VAT rates)
   - ☑ Вклучи единици мерка (Include units)
5. Click **Извези** (Export)
6. Save as: `onivo_items.csv`

#### Step 4: Export Invoices

1. Select **Фактури** (Invoices)
2. Choose date range (e.g., last 12 months)
3. Invoice types:
   - ☑ Излезни фактури (Outgoing invoices)
   - ☐ Влезни фактури (Incoming - optional)
4. Select format: **CSV** or **Excel**
5. Options:
   - ☑ Вклучи ставки (Include line items)
   - ☑ Вклучи ДДВ детали (Include VAT details)
   - ☑ Вклучи статус (Include status)
6. Click **Извези** (Export)
7. Save as: `onivo_invoices.csv`

#### Step 5: Export Payments

1. Select **Уплати** (Payments)
2. Choose same date range as invoices
3. Payment types:
   - ☑ Готовински уплати (Cash payments)
   - ☑ Банкарски уплати (Bank transfers)
   - ☑ Картички (Card payments)
4. Select format: **CSV** or **Excel**
5. Click **Извези** (Export)
6. Save as: `onivo_payments.csv`

### File Locations

Onivo typically saves exports to:
- **Windows**: `C:\Users\[Username]\Documents\Onivo\Exports\`
- **Desktop app**: Check Settings → Export Folder

### Data Cleaning Tips

#### Issue 1: Cyrillic Encoding

Onivo exports are UTF-8 by default, but verify:

1. Open CSV in Notepad++
2. Check Encoding menu shows "UTF-8"
3. If not, convert: Encoding → Convert to UTF-8

#### Issue 2: Date Format

Onivo uses `dd.mm.yyyy` format:
- Example: `25.11.2024`
- This is automatically recognized by Facturino

#### Issue 3: Tax ID Format

Onivo stores ЕДБ as:
- With prefix: `MK4080003501234` ✓
- Without prefix: `4080003501234` ✓ (prefix added automatically)

#### Issue 4: Status Translation

Onivo invoice statuses:

| Onivo (Cyrillic) | Onivo (English) | Facturino |
|------------------|----------------|-----------|
| Не испратена | Not sent | DRAFT |
| Испратена | Sent | SENT |
| Прегледана | Viewed | VIEWED |
| Доспеана | Overdue | OVERDUE |
| Платена | Paid | PAID |
| Делумно платена | Partially paid | PARTIALLY_PAID |
| Откажана | Cancelled | CANCELLED |

### Field Mapping: Onivo → Facturino

#### Customers

| Onivo Field (Cyrillic) | Onivo Field (Latin) | Facturino Field |
|------------------------|---------------------|-----------------|
| Партнер | Partner | `name` |
| ЕДБ | EDB | `vat_number` |
| Email | Email | `email` |
| Телефон | Phone | `phone` |
| Контакт | Contact | `contact_name` |
| Веб-страна | Website | `website` |
| Адреса | Address | `address_street_1` |
| Град | City | `city` |
| Поштенски број | Postal Code | `zip` |
| Држава | Country | `country` |

#### Items

| Onivo Field (Cyrillic) | Onivo Field (Latin) | Facturino Field |
|------------------------|---------------------|-----------------|
| Производ | Product | `name` |
| Опис | Description | `description` |
| Цена | Price | `price` |
| Единица | Unit | `unit_name` |
| Шифра | Code | `sku` |
| ДДВ стапка | VAT Rate | `tax_rate` |

#### Invoices

| Onivo Field (Cyrillic) | Onivo Field (Latin) | Facturino Field |
|------------------------|---------------------|-----------------|
| Број на фактура | Invoice Number | `invoice_number` |
| Купувач | Customer | `customer_name` |
| Датум на фактура | Invoice Date | `invoice_date` |
| Рок на плаќање | Payment Due | `due_date` |
| Основица | Base Amount | `sub_total` |
| ДДВ | VAT | `tax` |
| Вкупно | Total | `total` |
| Попуст % | Discount % | `discount` |
| Попуст износ | Discount Amount | `discount_val` |
| Забелешки | Notes | `notes` |
| Статус | Status | `status` |

#### Payments

| Onivo Field (Cyrillic) | Onivo Field (Latin) | Facturino Field |
|------------------------|---------------------|-----------------|
| Број на фактура | Invoice Number | `invoice_number` |
| Датум на уплата | Payment Date | `payment_date` |
| Износ | Amount | `amount` |
| Начин на плаќање | Payment Method | `payment_method` |
| Референца | Reference | `payment_number` |
| Забелешка | Note | `notes` |

### Onivo-Specific Considerations

**1. Multiple Companies**
- If you have multiple companies in Onivo, export each separately
- Import them as separate companies in Facturino

**2. Historical Data**
- Onivo keeps unlimited history
- Consider exporting last 1-2 years only for performance
- Older data can be kept in Onivo for archival

**3. Fiscal Year**
- Note Onivo's fiscal year setting
- Ensure date ranges align with your business needs

**4. VAT Rates**
- Onivo standard rates: 18%, 5%, 0%
- These map directly to Facturino

---

## Megasoft ERP

### About Megasoft

**Enterprise ERP System**
- Popular in medium-large Macedonian businesses
- Full ERP capabilities (accounting, inventory, HR)
- Mixed Macedonian/English interface
- SQL Server backend

### Export Instructions

#### Step 1: Access Export Module

1. Open Megasoft ERP
2. Log in with admin credentials
3. Go to **Podaci** (Data) → **Eksport** (Export)
4. Select **Eksport u datoteku** (Export to file)

#### Step 2: Export Configuration

**Database Selection:**
1. Choose your company database
2. Select fiscal year if prompted
3. Confirm date range

**Export Format:**
- **CSV** - For simple export
- **Excel** - For formatted export
- **XML** - For structured data (advanced)

#### Step 3: Export Customers (Partneri)

1. Module: **Partneri** (Partners)
2. Filter: **Kupci** (Customers only) or **Svi** (All partners)
3. Fields to include:
   - ☑ ParnerName
   - ☑ ParnerEDB (Tax ID)
   - ☑ ParnerEmail
   - ☑ ParnerTel (Phone)
   - ☑ ParnerAdresa (Address)
   - ☑ Kontakt (Contact person)
4. Export format: **CSV UTF-8**
5. Save as: `megasoft_customers.csv`

#### Step 4: Export Items (Artikli)

1. Module: **Artikli** (Items)
2. Category: **Svi artikli** (All items) or filter by category
3. Fields to include:
   - ☑ ArtikalNaziv (Item name)
   - ☑ ArtikalOpis (Description)
   - ☑ ArtikalCena (Price)
   - ☑ MernaEdinica (Unit)
   - ☑ ArtikalSifra (SKU)
   - ☑ DDVStapka (VAT rate)
4. Export format: **CSV** or **Excel**
5. Save as: `megasoft_items.csv`

#### Step 5: Export Invoices (Fakturi)

1. Module: **Fakturi** (Invoices)
2. Type: **Izlezni fakturi** (Outgoing invoices)
3. Date range: Specify start and end dates
4. Fields to include:
   - ☑ FakturaBroj (Invoice number)
   - ☑ Kupuvac (Customer)
   - ☑ FakturaDatum (Date)
   - ☑ RokNaPlakanje (Due date)
   - ☑ Osnovica (Subtotal)
   - ☑ DDV (VAT)
   - ☑ Vkupno (Total)
   - ☑ Stavki (Line items)
5. Export format: **XML** (recommended) or **CSV**
6. Save as: `megasoft_invoices.xml` or `.csv`

#### Step 6: Export Payments (Uplati)

1. Module: **Uplati** (Payments)
2. Payment type: **Primeni uplati** (Received payments)
3. Date range: Match invoice range
4. Fields to include:
   - ☑ FakturaBroj (Invoice reference)
   - ☑ DatumUplata (Payment date)
   - ☑ Iznos (Amount)
   - ☑ NachinPlakanje (Payment method)
   - ☑ Referenca (Reference)
5. Export format: **CSV**
6. Save as: `megasoft_payments.csv`

### Data Cleaning Tips

#### Issue 1: Mixed Encodings

Megasoft sometimes mixes encodings:

**Solution:**
1. Open file in Excel
2. Save As → CSV UTF-8
3. Verify Cyrillic displays correctly

#### Issue 2: XML Namespace

Megasoft XML exports include custom namespaces:

```xml
<Faktura xmlns:ms="http://megasoft.mk/schema">
  <FakturaBroj>FAK-001</FakturaBroj>
</Faktura>
```

**Solution:** The Migration Wizard handles Megasoft XML automatically

#### Issue 3: Decimal Separators

Megasoft uses comma decimals:
- `1.000,50` (one thousand point fifty)

**Solution:** Automatically converted by Facturino

#### Issue 4: Customer References

Megasoft uses internal customer IDs:

**Solution:**
1. Export customer names, not IDs
2. Or create customer mapping file

### Field Mapping: Megasoft → Facturino

#### Customers

| Megasoft Field | Description | Facturino Field |
|----------------|-------------|-----------------|
| ParnerName | Partner name | `name` |
| ParnerEDB | Tax ID (EDB) | `vat_number` |
| ParnerEmail | Email | `email` |
| ParnerTel | Phone | `phone` |
| Kontakt | Contact person | `contact_name` |
| Website | Website | `website` |
| ParnerAdresa | Address | `address_street_1` |
| Grad | City | `city` |
| PostanskiBroj | Postal code | `zip` |
| Drzava | Country | `country` |

#### Items

| Megasoft Field | Description | Facturino Field |
|----------------|-------------|-----------------|
| ArtikalNaziv | Item name | `name` |
| ArtikalOpis | Description | `description` |
| ArtikalCena | Price | `price` |
| MernaEdinica | Unit of measure | `unit_name` |
| ArtikalSifra | Item code/SKU | `sku` |
| DDVStapka | VAT rate | `tax_rate` |

#### Invoices

| Megasoft Field | Description | Facturino Field |
|----------------|-------------|-----------------|
| FakturaBroj | Invoice number | `invoice_number` |
| Kupuvac | Customer name | `customer_name` |
| FakturaDatum | Invoice date | `invoice_date` |
| RokNaPlakanje | Due date | `due_date` |
| Osnovica | Base amount | `sub_total` |
| DDV | VAT amount | `tax` |
| Vkupno | Total | `total` |
| Popust | Discount % | `discount` |
| PopustIznos | Discount amount | `discount_val` |
| Zabeleska | Notes | `notes` |
| Status | Status | `status` |

#### Payments

| Megasoft Field | Description | Facturino Field |
|----------------|-------------|-----------------|
| FakturaBroj | Invoice number | `invoice_number` |
| DatumUplata | Payment date | `payment_date` |
| Iznos | Amount | `amount` |
| NachinPlakanje | Payment method | `payment_method` |
| Referenca | Reference | `payment_number` |
| Zabeleska | Note | `notes` |

### Megasoft-Specific Considerations

**1. SQL Server Database**
- Megasoft stores data in SQL Server
- Direct database export is possible (advanced users)
- Consult IT team for database credentials

**2. Multi-Module System**
- Megasoft has inventory, CRM, HR modules
- Only export accounting/invoicing data
- Other modules stay in Megasoft if needed

**3. Custom Fields**
- Megasoft allows custom fields
- These won't map automatically
- Add to "notes" field or skip

**4. Warehouse Locations**
- If using inventory module, note warehouse codes
- Facturino doesn't have warehouse management (yet)

---

## Effect Plus

### About Effect Plus

**Mid-Market Accounting Software**
- Popular in small-medium Macedonian businesses
- Affordable pricing
- Good for basic accounting needs
- Local support network

### Export Instructions

#### Step 1: Open Export Tool

1. Launch Effect Plus
2. Login with your credentials
3. Navigate to **Alati** (Tools) → **Export podataka** (Data Export)

#### Step 2: Select Module

Choose module to export:
- **Klienti** (Customers)
- **Proizvodi** (Products/Items)
- **Fakturi** (Invoices)
- **Uplati** (Payments)

#### Step 3: Export Customers

1. Module: **Klienti**
2. Filter:
   - Date: **Svi klienti** (All customers)
   - Type: **Aktivni** (Active only) or **Svi** (All)
3. Format: **CSV** or **Excel**
4. Fields:
   - ☑ Klient_Naziv (Name)
   - ☑ Klient_EDB (Tax ID)
   - ☑ Klient_Email
   - ☑ Klient_Telefon
   - ☑ Klient_Adresa
5. Save as: `effectplus_customers.csv`

#### Step 4: Export Items

1. Module: **Proizvodi**
2. Category: **Sve kategorije** (All categories)
3. Format: **CSV**
4. Fields:
   - ☑ Proizvod_Naziv (Name)
   - ☑ Proizvod_Opis (Description)
   - ☑ Proizvod_Cena (Price)
   - ☑ Proizvod_Edinica (Unit)
   - ☑ Proizvod_DDV (VAT rate)
5. Save as: `effectplus_items.csv`

#### Step 5: Export Invoices

1. Module: **Fakturi**
2. Date range: Last 12 months (or custom)
3. Type: **Izlezni** (Outgoing)
4. Format: **Excel** (recommended) or **CSV**
5. Options:
   - ☑ Vkluchi stavki (Include line items)
   - ☑ Vkluchi DDV detalji (Include VAT details)
6. Save as: `effectplus_invoices.xlsx`

#### Step 6: Export Payments

1. Module: **Uplati**
2. Date range: Match invoice range
3. Filter: **Primeni uplati** (Received payments)
4. Format: **CSV**
5. Save as: `effectplus_payments.csv`

### Data Cleaning Tips

#### Issue 1: Date Format

Effect Plus uses `dd/mm/yyyy`:
- Example: `25/11/2024`
- Automatically recognized

#### Issue 2: Empty Columns

Effect Plus exports include many empty columns:

**Solution:**
1. Open in Excel
2. Delete empty columns
3. Keep only needed fields
4. Re-save as CSV UTF-8

#### Issue 3: Customer Codes

Effect Plus uses customer codes (e.g., `K-001`):

**Solution:**
- Use customer names for import, not codes
- Or maintain code in "notes" field

### Field Mapping: Effect Plus → Facturino

#### Customers

| Effect Plus Field | Facturino Field |
|-------------------|-----------------|
| Klient_Naziv | `name` |
| Klient_EDB | `vat_number` |
| Klient_Email | `email` |
| Klient_Telefon | `phone` |
| Klient_Kontakt | `contact_name` |
| Klient_Website | `website` |
| Klient_Adresa | `address_street_1` |
| Klient_Grad | `city` |
| Klient_Postanski_Broj | `zip` |
| Klient_Drzava | `country` |

#### Items

| Effect Plus Field | Facturino Field |
|-------------------|-----------------|
| Proizvod_Naziv | `name` |
| Proizvod_Opis | `description` |
| Proizvod_Cena | `price` |
| Proizvod_Edinica | `unit_name` |
| Proizvod_Sifra | `sku` |
| Proizvod_DDV | `tax_rate` |

#### Invoices

| Effect Plus Field | Facturino Field |
|-------------------|-----------------|
| Faktura_Broj | `invoice_number` |
| Klient_Naziv | `customer_name` |
| Faktura_Datum | `invoice_date` |
| Rok_Plakanje | `due_date` |
| Osnovica | `sub_total` |
| DDV_Iznos | `tax` |
| Faktura_Vkupno | `total` |
| Popust_Procenat | `discount` |
| Popust_Iznos | `discount_val` |
| Zabeleska | `notes` |
| Faktura_Status | `status` |

#### Payments

| Effect Plus Field | Facturino Field |
|-------------------|-----------------|
| Faktura_Broj | `invoice_number` |
| Uplata_Datum | `payment_date` |
| Uplata_Iznos | `amount` |
| Nacin_Plakanje | `payment_method` |
| Uplata_Broj | `payment_number` |
| Uplata_Zabeleska | `notes` |

---

## Eurofaktura

### About Eurofaktura

**Serbian Accounting Software (used in Macedonia)**
- Popular in Serbian-speaking regions
- Good e-invoice support
- Serbian language interface
- Compatible with Serbian/Macedonian tax systems

### Export Instructions

#### Step 1: Access Export

1. Open Eurofaktura
2. Navigate to **Podešavanja** (Settings) → **Import/Export**
3. Select **Export u CSV/Excel** (Export to CSV/Excel)

#### Step 2: Export Customers (Kupci)

1. Modul: **Kupci** (Customers)
2. Filter: **Svi kupci** (All customers)
3. Format: **CSV UTF-8** (important for Serbian Cyrillic)
4. Polja (Fields):
   - ☑ Kupac_Naziv (Name)
   - ☑ Kupac_PIB (Tax ID)
   - ☑ Kupac_Email
   - ☑ Kupac_Telefon
   - ☑ Kupac_Adresa
5. Click **Exportuj** (Export)
6. Save as: `eurofaktura_customers.csv`

#### Step 3: Export Items (Artikli)

1. Modul: **Artikli** (Items)
2. Format: **CSV**
3. Polja:
   - ☑ Artikal_Naziv
   - ☑ Artikal_Opis
   - ☑ Artikal_Cena
   - ☑ Artikal_JM (Unit of measure)
   - ☑ PDV_Stopa (VAT rate)
4. Click **Exportuj**
5. Save as: `eurofaktura_items.csv`

#### Step 4: Export Invoices (Fakture)

1. Modul: **Fakture**
2. Vrsta: **Izdati** (Issued invoices)
3. Period: Select date range
4. Format: **Excel** or **XML**
5. Opcije:
   - ☑ Uključi stavke (Include line items)
   - ☑ Uključi PDV (Include VAT)
6. Click **Exportuj**
7. Save as: `eurofaktura_invoices.xlsx`

#### Step 5: Export Payments (Uplate)

1. Modul: **Uplate** (Payments)
2. Period: Match invoice range
3. Format: **CSV**
4. Polja:
   - ☑ Faktura_Broj
   - ☑ Datum_Uplate
   - ☑ Iznos
   - ☑ Način_Plaćanja
5. Click **Exportuj**
6. Save as: `eurofaktura_payments.csv`

### Data Cleaning Tips

#### Issue 1: Serbian Cyrillic vs Latin

Eurofaktura supports both scripts:
- Cyrillic: Купац, Фактура
- Latin: Kupac, Faktura

**Solution:** Both work in Facturino (UTF-8 encoded)

#### Issue 2: PIB vs ЕДБ

- Serbian: PIB (Poreski Identifikacioni Broj)
- Macedonian: ЕДБ (Едностран Даночен Број)

**Solution:** Both map to `vat_number` in Facturino

#### Issue 3: Date Separators

Eurofaktura uses `dd.mm.yyyy.` (note trailing dot):
- Example: `25.11.2024.`

**Solution:** Automatically handled, trailing dot removed

### Field Mapping: Eurofaktura → Facturino

#### Customers (Serbian → Facturino)

| Eurofaktura Field | Facturino Field |
|-------------------|-----------------|
| Kupac_Naziv / Купац_Назив | `name` |
| Kupac_PIB / Купац_ПИБ | `vat_number` |
| Kupac_Email | `email` |
| Kupac_Telefon / Телефон | `phone` |
| Kontakt_Osoba / Контакт особа | `contact_name` |
| Website | `website` |
| Adresa / Адреса | `address_street_1` |
| Grad / Град | `city` |
| Poštanski_Broj | `zip` |
| Država / Држава | `country` |

#### Items

| Eurofaktura Field | Facturino Field |
|-------------------|-----------------|
| Artikal_Naziv / Артикал_Назив | `name` |
| Artikal_Opis / Опис | `description` |
| Artikal_Cena / Цена | `price` |
| Artikal_JM | `unit_name` |
| Artikal_Sifra / Шифра | `sku` |
| PDV_Stopa | `tax_rate` |

#### Invoices

| Eurofaktura Field | Facturino Field |
|-------------------|-----------------|
| Faktura_Broj / Фактура_Број | `invoice_number` |
| Kupac_Naziv | `customer_name` |
| Faktura_Datum / Датум | `invoice_date` |
| Rok_Plaćanja | `due_date` |
| Osnovica / Основица | `sub_total` |
| PDV / ПДВ | `tax` |
| Faktura_Ukupno / Укупно | `total` |
| Popust / Попуст | `discount` |
| Popust_Iznos | `discount_val` |
| Napomena / Напомена | `notes` |
| Status | `status` |

#### Payments

| Eurofaktura Field | Facturino Field |
|-------------------|-----------------|
| Faktura_Broj | `invoice_number` |
| Datum_Uplate / Датум_Уплате | `payment_date` |
| Iznos / Износ | `amount` |
| Način_Plaćanja | `payment_method` |
| Referenca | `payment_number` |
| Napomena | `notes` |

### Eurofaktura-Specific Considerations

**1. E-Invoice Integration**
- Eurofaktura has e-invoice (e-Faktura) support
- XML exports compatible with Facturino
- Can migrate e-invoice data

**2. Multi-Currency**
- Eurofaktura supports EUR, RSD, USD
- Convert to MKD during import
- Or maintain original currency (if needed)

**3. Serbian Tax Rates**
- Serbian PDV: 20%, 10%, 0%
- Macedonian DDV: 18%, 5%, 0%
- Map appropriately during import

---

## Manager.io

### About Manager.io

**International Accounting Software**
- Cloud and desktop versions
- Used globally (including Macedonia)
- English interface
- Free for small businesses

### Export Instructions

#### Step 1: Access Export Function

1. Open Manager.io (desktop or https://manager.io)
2. Select your business
3. Go to tab you want to export

#### Step 2: Export Customers

1. Click **Customers** tab
2. Click **Export** button (top right)
3. Choose format:
   - **Excel** (recommended)
   - **Tab-separated**
4. File downloads automatically
5. Rename to: `manager_customers.xlsx`

#### Step 3: Export Items

1. Click **Items** tab
2. Click **Export** button
3. Choose **Excel** format
4. Save as: `manager_items.xlsx`

#### Step 4: Export Sales Invoices

1. Click **Sales Invoices** tab
2. Set date range (if available)
3. Click **Export** button
4. Choose **Excel** format
5. Save as: `manager_invoices.xlsx`

**Note:** Manager.io exports invoice summary, not line items separately

#### Step 5: Export Receipts (Payments)

1. Click **Receipts** tab
2. Click **Export** button
3. Choose **Excel** format
4. Save as: `manager_receipts.xlsx`

### Data Cleaning Tips

#### Issue 1: Tab-Separated Format

Manager.io sometimes exports as TSV (tab-separated):

**Solution:**
1. Open in Excel
2. Data will auto-separate into columns
3. Save As → CSV UTF-8

#### Issue 2: Date Format

Manager.io uses local format based on settings:
- Can be `dd/MM/yyyy` or `MM/dd/yyyy`

**Solution:**
1. Check a few dates to identify format
2. Configure date format in Facturino import wizard

#### Issue 3: Line Items

Manager.io doesn't export line items separately:

**Solution:**
1. Line items are embedded in invoice notes
2. May need manual entry for detailed line items
3. Or use custom export (Advanced)

### Field Mapping: Manager.io → Facturino

#### Customers

| Manager.io Field | Facturino Field |
|------------------|-----------------|
| Customer Name | `name` |
| Tax ID | `vat_number` |
| Email | `email` |
| Phone | `phone` |
| Contact Person | `contact_name` |
| Website | `website` |
| Address | `address_street_1` |
| City | `city` |
| Postal Code | `zip` |
| Country | `country` |

#### Items

| Manager.io Field | Facturino Field |
|------------------|-----------------|
| Item Name | `name` |
| Description | `description` |
| Unit Price | `price` |
| Unit | `unit_name` |
| Item Code | `sku` |
| Tax Rate | `tax_rate` |

#### Sales Invoices

| Manager.io Field | Facturino Field |
|------------------|-----------------|
| Invoice Number | `invoice_number` |
| Customer | `customer_name` |
| Date | `invoice_date` |
| Due Date | `due_date` |
| Subtotal | `sub_total` |
| Tax | `tax` |
| Total | `total` |
| Discount | `discount_val` |
| Notes | `notes` |
| Status | `status` |

#### Receipts (Payments)

| Manager.io Field | Facturino Field |
|------------------|-----------------|
| Invoice Number | `invoice_number` |
| Date | `payment_date` |
| Amount | `amount` |
| Payment Method | `payment_method` |
| Reference | `payment_number` |
| Description | `notes` |

### Manager.io-Specific Considerations

**1. Cloud vs Desktop**
- Both versions export identically
- Cloud version: Export downloads directly
- Desktop: Exports save to Documents folder

**2. Multi-Business**
- Manager.io supports multiple businesses
- Export each business separately
- Import as separate companies in Facturino

**3. Custom Fields**
- Manager.io allows custom fields
- These won't auto-map
- Add to notes or skip

**4. Localization**
- Manager.io is in English
- Translate key terms for Macedonian users:
  - Customer → Клиент
  - Invoice → Фактура
  - Payment → Уплата

---

## Field Mapping Tables

### Complete Field Reference

#### All Systems → Facturino (Customers)

| Onivo | Megasoft | Effect Plus | Eurofaktura | Manager.io | Facturino |
|-------|----------|-------------|-------------|------------|-----------|
| Партнер | ParnerName | Klient_Naziv | Kupac_Naziv | Customer Name | `name` |
| ЕДБ | ParnerEDB | Klient_EDB | Kupac_PIB | Tax ID | `vat_number` |
| Email | ParnerEmail | Klient_Email | Kupac_Email | Email | `email` |
| Телефон | ParnerTel | Klient_Telefon | Kupac_Telefon | Phone | `phone` |
| Контакт | Kontakt | Klient_Kontakt | Kontakt_Osoba | Contact Person | `contact_name` |
| Веб-страна | Website | Klient_Website | Website | Website | `website` |
| Адреса | ParnerAdresa | Klient_Adresa | Adresa | Address | `address_street_1` |
| Град | Grad | Klient_Grad | Grad | City | `city` |
| Поштенски број | PostanskiBroj | Klient_Postanski_Broj | Poštanski_Broj | Postal Code | `zip` |
| Држава | Drzava | Klient_Drzava | Država | Country | `country` |

#### All Systems → Facturino (Invoices)

| Onivo | Megasoft | Effect Plus | Eurofaktura | Manager.io | Facturino |
|-------|----------|-------------|-------------|------------|-----------|
| Број на фактура | FakturaBroj | Faktura_Broj | Faktura_Broj | Invoice Number | `invoice_number` |
| Купувач | Kupuvac | Klient_Naziv | Kupac_Naziv | Customer | `customer_name` |
| Датум на фактура | FakturaDatum | Faktura_Datum | Faktura_Datum | Date | `invoice_date` |
| Рок на плаќање | RokNaPlakanje | Rok_Plakanje | Rok_Plaćanja | Due Date | `due_date` |
| Основица | Osnovica | Osnovica | Osnovica | Subtotal | `sub_total` |
| ДДВ | DDV | DDV_Iznos | PDV | Tax | `tax` |
| Вкупно | Vkupno | Faktura_Vkupno | Faktura_Ukupno | Total | `total` |
| Попуст % | Popust | Popust_Procenat | Popust | - | `discount` |
| Попуст износ | PopustIznos | Popust_Iznos | Popust_Iznos | Discount | `discount_val` |
| Забелешки | Zabeleska | Zabeleska | Napomena | Notes | `notes` |
| Статус | Status | Faktura_Status | Status | Status | `status` |

---

## Migration Checklists

### Pre-Migration Checklist

Use this before starting migration:

#### Data Preparation
- [ ] Backup current accounting software database
- [ ] Export all required modules (customers, items, invoices, payments)
- [ ] Verify file encoding (UTF-8 for Cyrillic)
- [ ] Check for missing required fields
- [ ] Clean up duplicate records
- [ ] Verify date formats are consistent
- [ ] Test with small sample (10-20 records)

#### System Preparation
- [ ] Facturino account created
- [ ] Company set up in Facturino
- [ ] Administrator access confirmed
- [ ] Migration Wizard feature enabled
- [ ] Browser updated (Chrome/Firefox/Safari)
- [ ] Stable internet connection
- [ ] Sufficient disk space for files

#### Documentation
- [ ] Export instructions reviewed for your software
- [ ] Field mapping table printed/bookmarked
- [ ] USER_GUIDE.md read
- [ ] CSV_FORMAT_GUIDE.md referenced
- [ ] Support contact information noted

### Migration Process Checklist

Follow during actual migration:

#### Phase 1: Customers
- [ ] Export customers from old software
- [ ] Clean and verify customer data
- [ ] Upload to Facturino Migration Wizard
- [ ] Review auto-mapped fields
- [ ] Correct any mapping errors
- [ ] Run dry-run validation
- [ ] Start import
- [ ] Verify sample customers in Facturino
- [ ] Download import report

#### Phase 2: Items
- [ ] Export items/products
- [ ] Verify prices and tax rates
- [ ] Upload to wizard
- [ ] Map fields
- [ ] Import items
- [ ] Verify sample items

#### Phase 3: Invoices
- [ ] Export invoices (with line items if possible)
- [ ] Ensure date range is correct
- [ ] Upload to wizard
- [ ] Map fields carefully (most complex)
- [ ] Verify calculations (subtotal + tax = total)
- [ ] Run dry-run (important!)
- [ ] Import invoices
- [ ] Verify sample invoices with line items

#### Phase 4: Payments
- [ ] Export payments
- [ ] Verify invoice references exist
- [ ] Upload to wizard
- [ ] Map payment methods correctly
- [ ] Import payments
- [ ] Verify payments linked to invoices

#### Phase 5: Verification
- [ ] Check total counts match (customers, items, invoices, payments)
- [ ] Verify financial totals (receivables, revenue)
- [ ] Test searching and filtering
- [ ] Generate sample reports
- [ ] Verify Cyrillic text displays correctly
- [ ] Check tax calculations
- [ ] Confirm invoice statuses

### Post-Migration Checklist

After successful migration:

#### Data Integrity
- [ ] Random sample verification (10-20 records each type)
- [ ] Financial reconciliation (totals match old system)
- [ ] Customer contact information verified
- [ ] Invoice numbering sequence checked
- [ ] Payment allocations verified
- [ ] Tax reports generated and compared

#### System Configuration
- [ ] Invoice templates configured
- [ ] Email settings tested
- [ ] Payment gateways connected (if applicable)
- [ ] User accounts created
- [ ] Permissions assigned
- [ ] Company logo uploaded
- [ ] Tax rates confirmed

#### Training & Transition
- [ ] Team trained on Facturino
- [ ] Old system kept for reference (6-12 months)
- [ ] Migration report archived
- [ ] Customers notified of new invoice format (if different)
- [ ] Accounting period closed in old system
- [ ] First invoices issued from Facturino

---

## Common Issues

### Issue 1: Cyrillic Text Displays as ���

**Affects:** Onivo, Megasoft, Effect Plus, Eurofaktura
**Cause:** File not UTF-8 encoded
**Solution:**
1. Open file in Excel
2. Save As → CSV UTF-8 (Comma delimited)
3. Re-upload to wizard
4. Verify preview shows Cyrillic correctly

---

### Issue 2: Customer Not Found Errors

**Affects:** All systems (during invoice import)
**Cause:** Customer names don't match exactly
**Solution:**
1. Import customers BEFORE invoices
2. Check spelling and spacing
3. Ensure Cyrillic vs Latin script matches
4. Use same customer names in both files

---

### Issue 3: Date Format Not Recognized

**Affects:** All systems
**Cause:** Inconsistent date formats in file
**Solution:**
1. Open file in Excel
2. Format all date columns as: dd.mm.yyyy
3. Use same format throughout entire file
4. Avoid mixed formats (dd/mm vs dd.mm)

---

### Issue 4: Tax Totals Don't Match

**Affects:** All systems (invoices)
**Cause:** Rounding differences or formula errors
**Solution:**
1. Verify: subtotal + tax - discount = total
2. Check tax rate percentage (18%, not 0.18)
3. Round amounts to 2 decimal places
4. Recalculate if needed

---

### Issue 5: Payment Method Not Recognized

**Affects:** All systems (payments)
**Cause:** Non-standard payment method values
**Solution:**
- Map to standard values:
  - `BANK_TRANSFER` - for bank, wire, transfer
  - `CASH` - for cash, готовина
  - `CARD` - for card, картичка, credit card
  - `CHECK` - for check, чек
  - `OTHER` - for anything else

---

### Issue 6: Duplicate Invoice Numbers

**Affects:** All systems
**Cause:** Invoice numbers already exist in Facturino
**Solution:**
1. Add prefix to old invoice numbers (e.g., OLD-FAK-001)
2. Or start new numbering sequence
3. Or skip duplicates (wizard will warn)

---

### Issue 7: Large File Upload Timeout

**Affects:** All systems (>100MB files)
**Cause:** File too large, slow connection
**Solution:**
1. Split file into smaller chunks (10,000 records each)
2. Import in batches
3. Or compress file before upload
4. Use wired connection (not Wi-Fi)

---

### Issue 8: XML Parsing Errors

**Affects:** Megasoft, Eurofaktura (XML exports)
**Cause:** Invalid XML structure
**Solution:**
1. Validate XML file (use online validator)
2. Re-export from source software
3. Try CSV format instead
4. Contact support if persists

---

## Support Resources

### Documentation

- **User Guide**: [USER_GUIDE.md](USER_GUIDE.md) - Complete guide
- **Quick Start**: [QUICK_START.md](QUICK_START.md) - 5-minute guide
- **CSV Format**: [CSV_FORMAT_GUIDE.md](CSV_FORMAT_GUIDE.md) - Format specs

### Contact Support

- **Email**: support@facturino.mk
- **Forum**: forum.facturino.mk
- **Phone**: +389 2 xxx xxxx (business hours)
- **Live Chat**: Available in app (9 AM - 5 PM)

### Community

- **Facebook**: facebook.com/facturino
- **LinkedIn**: linkedin.com/company/facturino
- **YouTube**: youtube.com/facturino (video tutorials)

---

## Appendix: Software Version Compatibility

| Software | Versions Tested | Status |
|----------|----------------|--------|
| **Onivo** | 8.x, 9.x, 10.x | ✅ Fully supported |
| **Megasoft** | 2020, 2021, 2022, 2023, 2024 | ✅ Fully supported |
| **Effect Plus** | 3.x, 4.x | ✅ Fully supported |
| **Eurofaktura** | 2019-2024 | ✅ Fully supported |
| **Manager.io** | Cloud & Desktop (all versions) | ✅ Fully supported |

---

**Document Version:** 1.0
**Last Updated:** November 12, 2025
**Maintained by:** Facturino Team

---

© 2025 Facturino. All rights reserved.

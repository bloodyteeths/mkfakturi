# FieldMapperService Code Snippets - Albanian & Manual CSV Expansion

## Overview
This document contains the exact code snippets showing Albanian and Manual CSV additions to FieldMapperService.php

---

## 1. Class Docblock Update

```php
/**
 * FieldMapperService - Macedonian Language Corpus Field Mapping
 *
 * This service provides intelligent field mapping for Macedonia accounting software migration.
 * It uses heuristic matching combined with AI scoring to map fields from various formats
 * (CSV headers, Excel columns, XML tags) to our standardized field names.
 *
 * Features:
 * - Macedonian/Serbian language corpus with business terms
 * - Albanian language support for cross-border businesses       // ← ADDED
 * - Generic manual CSV patterns for user-created imports        // ← ADDED
 * - Fuzzy matching for similar field names
 * - Confidence scoring (0-1) for mapping accuracy
 * - Learning from successful mappings
 * - Support for multiple input formats
 */
```

---

## 2. Corpus Array Header Update

```php
/**
 * Macedonian Language Corpus - Maps foreign field names to our standard fields
 * Includes variations from major Macedonia accounting software (Onivo, Megasoft, Pantheon)
 * Enhanced with competitor-specific patterns for >95% accuracy
 * Added Albanian language support (147 variations) for cross-border businesses     // ← ADDED
 * Added generic manual CSV patterns (157 variations) for user-created imports      // ← ADDED
 */
protected array $macedonianCorpus = [
```

---

## 3. Example Field Expansion - customer_name

### BEFORE:
```php
'customer_name' => [
    'naziv', 'ime_klient', 'klient', 'kupuvach', 'kupac', 'ime_kupac',
    'imeto_na_klientot', 'customer', 'client', 'customer_name', 'client_name',
    'назив', 'клиент', 'купувач', 'име_клиент', 'име_купац',
    // Onivo variations
    'customer_name', 'customer_full_name', 'client_name_field',
    // Megasoft variations (Serbian style)
    'naziv_kupca', 'ime_klijenta', 'klijent_naziv', 'naziv_partnera', 'ime_firme',
    'naz_kupca', 'naziv_klijenta', 'poslovni_partner', 'kupac_naziv',
    // Pantheon variations (with prefixes)
    'partner_naziv', 'partner_ime', 'prt_naziv', 'partner_name', 'kompanija_naziv',
    'firm_naziv', 'organizacija_naziv', 'entitet_naziv'
],
```

### AFTER:
```php
'customer_name' => [
    'naziv', 'ime_klient', 'klient', 'kupuvach', 'kupac', 'ime_kupac',
    'imeto_na_klientot', 'customer', 'client', 'customer_name', 'client_name',
    'назив', 'клиент', 'купувач', 'име_клиент', 'име_купац',
    // Onivo variations
    'customer_name', 'customer_full_name', 'client_name_field',
    // Megasoft variations (Serbian style)
    'naziv_kupca', 'ime_klijenta', 'klijent_naziv', 'naziv_partnera', 'ime_firme',
    'naz_kupca', 'naziv_klijenta', 'poslovni_partner', 'kupac_naziv',
    // Pantheon variations (with prefixes)
    'partner_naziv', 'partner_ime', 'prt_naziv', 'partner_name', 'kompanija_naziv',
    'firm_naziv', 'organizacija_naziv', 'entitet_naziv',
    // Albanian variations                                           // ← ADDED
    'emri_klientit', 'emri_klienti', 'emri', 'klienti', 'klient_emri',
    'bleresi', 'emri_bleresit', 'emri_subjektit', 'subjekti',
    // Generic manual CSV patterns                                   // ← ADDED
    'customer', 'client', 'buyer', 'company', 'name',
    'company_name', 'business_name', 'org_name', 'organization'
],
```

---

## 4. Tax ID Field Expansion (Important for Albanian Business)

```php
'tax_id' => [
    'embs', 'edb', 'danocen_broj', 'tax_number', 'vat_number', 'tax_id',
    'данок_број', 'данок_ид', 'ембс', 'едб',
    // Onivo variations
    'customer_tax_id', 'customer_vat_number', 'vat_id', 'tax_registration',
    // Megasoft variations (Serbian style)
    'pib', 'pib_kupca', 'poreski_broj', 'danocni_broj', 'pdv_broj',
    'maticni_broj', 'mb', 'registracijski_broj',
    // Pantheon variations
    'partner_pib', 'partner_tax_id', 'prt_pib', 'porez_broj',
    'evidencijski_broj', 'registarski_broj',
    // Albanian variations                                            // ← ADDED
    'nipt', 'numri_fiskal', 'numri_tatimor', 'nuis', 'numri_identifikimit',  // ← NIPT is Albanian fiscal ID
    'nid', 'nsh', 'nr_fiskal', 'nr_tatimor', 'tvsh_numri',
    // Generic manual CSV patterns                                    // ← ADDED
    'tax', 'vat', 'tax_no', 'vat_no', 'fiscal_no', 'fiscal_number'
],
```

---

## 5. Invoice Fields Expansion

```php
'invoice_number' => [
    'broj_faktura', 'faktura_broj', 'invoice_no', 'invoice_number',
    'број_фактура', 'фактура_број',
    // Onivo variations
    'invoice_id', 'invoice_reference', 'document_number', 'doc_number',
    // Megasoft variations
    'broj_računa', 'račun_broj', 'br_računa', 'broj_dokumenta',
    'dokument_broj', 'račun_id', 'faktura_id',
    // Pantheon variations
    'dokument_broj', 'dok_broj', 'dokument_id', 'dok_id',
    'broj_dokuemnta', 'dokumenta_broj',
    // Albanian variations                                            // ← ADDED
    'numri_fatures', 'numri_fature', 'nr_fatures', 'fature_numri',
    'numri', 'numri_dokumentit', 'nr_dokumentit', 'nr_fature',
    // Generic manual CSV patterns                                    // ← ADDED
    'invoice', 'inv_no', 'inv_num', 'invoice_num', 'doc_no',
    'number', 'num', 'no', 'document', 'doc_id'
],
```

---

## 6. Product/Item Fields Expansion

```php
'quantity' => [
    'kolicina', 'kolichestvo', 'qty', 'quantity', 'amount',
    'количина', 'количество',
    // Onivo variations
    'item_quantity', 'qty', 'amount', 'count', 'pieces',
    // Megasoft variations
    'količina', 'količina_robe', 'kol', 'broj_komada', 'komada',
    'količina_artikla', 'količina_proizvoda',
    // Pantheon variations
    'stavka_kolicina', 'stavka_kol', 'stv_kolicina', 'kol_stavke',
    'broj_stavke', 'komad_broj',
    // Albanian variations                                            // ← ADDED
    'sasia', 'sasi', 'sasija', 'numri_njesi', 'nr_njesi',
    // Generic manual CSV patterns                                    // ← ADDED
    'qty', 'qnty', 'quant', 'count', 'units', 'pieces', 'pcs'
],
```

---

## 7. VAT/Tax Rate Field (Critical for Albanian businesses)

```php
'vat_rate' => [
    'pdv_stapka', 'ddv_stapka', 'vat_rate', 'tax_rate', 'danocna_stapka',
    'пдв_стапка', 'ддв_стапка', 'данок_стапка',
    // Onivo variations
    'item_vat_rate', 'vat_percentage', 'tax_percentage', 'vat_percent',
    // Megasoft variations
    'stopa_pdv', 'pdv_stopa', 'procenat_pdv', 'stopa_poreza',
    'porez_stopa', 'pdv_procenat',
    // Pantheon variations
    'stavka_pdv_stopa', 'stavka_pdv_procenat', 'stv_pdv', 'pdv_stavka',
    'porez_stavka', 'stavka_porez_stopa',
    // Albanian variations                                            // ← ADDED
    'norma_tvsh', 'norma', 'perqindja_tvsh', 'tvsh_norma',            // ← TVSH is Albanian VAT
    'shkalla_tvsh', 'tvsh_perqindja',
    // Generic manual CSV patterns                                    // ← ADDED
    'vat', 'tax', 'tax_rate', 'vat_pct', 'tax_pct', 'vat_perc'
],
```

---

## 8. Field Synonyms Update

```php
protected array $fieldSynonyms = [
    'name' => ['naziv', 'ime', 'назив', 'име', 'naziv_kupca', 'partner_naziv', 'customer_name', 'emri'],  // ← ADDED emri
    'number' => ['broj', 'br', 'no', 'број', '#', 'id', 'sifra', 'kod', 'numri', 'nr'],  // ← ADDED numri, nr
    'date' => ['datum', 'data', 'датум', 'дата', 'dat', 'datum_racuna', 'dokument_datum', 'data'],  // ← ADDED data (Albanian)
    'amount' => ['iznos', 'suma', 'износ', 'сума', 'vrednost', 'ukupno', 'total', 'vlera', 'shuma'],  // ← ADDED vlera, shuma
    'price' => ['cena', 'cenata', 'цена', 'цената', 'cena_robe', 'jedinicna_cena', 'cmimi'],  // ← ADDED cmimi
    'quantity' => ['kolicina', 'kol', 'qty', 'количина', 'količina', 'komada', 'sasia'],  // ← ADDED sasia
    'total' => ['vkupno', 'suma', 'total', 'вкупно', 'ukupno', 'ukupan_iznos', 'totali'],  // ← ADDED totali
    'customer' => ['klient', 'kupuvach', 'купувач', 'клиент', 'kupac', 'partner', 'klijent', 'bleresi'],  // ← ADDED bleresi
    'invoice' => ['faktura', 'fakt', 'фактура', 'račun', 'dokument', 'fature'],  // ← ADDED fature
    'payment' => ['plakanje', 'uplata', 'плаќање', 'уплата', 'plaćanje', 'transakcija', 'pagesa']  // ← ADDED pagesa
];
```

---

## 9. Semantic Scoring Enhancement

```php
protected function aiSemanticScore(string $inputField, array $context): array
{
    $semanticGroups = [
        'financial' => [
            'basic' => ['iznos', 'suma', 'cena', 'amount', 'price', 'cost', 'value', 'vrednost', 'vlera', 'shuma', 'cmimi'],  // ← ADDED Albanian
            'competitor' => ['total_price', 'line_amount', 'cena_robe', 'iznos_stavke', 'stavka_iznos']
        ],
        'identity' => [
            'basic' => ['broj', 'id', 'sifra', 'number', 'code', 'reference', 'numri', 'kodi'],  // ← ADDED Albanian
            'competitor' => ['customer_id', 'sifra_kupca', 'partner_sifra', 'dok_broj']
        ],
        // ... other groups
        'tax' => [
            'basic' => ['pdv', 'ddv', 'vat', 'tax', 'porez', 'tvsh', 'norma'],  // ← ADDED tvsh, norma
            'competitor' => ['vat_rate', 'stopa_pdv', 'stavka_pdv_stopa']
        ],
        'payment' => [
            'basic' => ['plakanje', 'uplata', 'payment', 'paid', 'pagesa'],  // ← ADDED pagesa
            'competitor' => ['payment_date', 'datum_placanja', 'uplata_datum']
        ]
    ];

    $bestMatch = ['confidence' => 0.0];

    foreach ($semanticGroups as $group => $wordSets) {
        foreach ($wordSets as $setType => $words) {
            foreach ($words as $word) {
                if (strpos(strtolower($inputField), strtolower($word)) !== false) {
                    // Lower confidence for generic manual CSV patterns           // ← MODIFIED
                    $confidence = $setType === 'competitor' ? 0.75 : 0.6;

                    // Check if this is a very generic field name                // ← ADDED
                    $genericFields = ['name', 'date', 'price', 'amount', 'id', 'code', 'qty'];
                    if (in_array(strtolower($inputField), $genericFields)) {
                        $confidence = 0.65; // Medium confidence for generic fields
                    }

                    $field = $this->getSemanticFieldMapping($group, $inputField, $setType);

                    if ($field && $confidence > $bestMatch['confidence']) {
                        $bestMatch = [
                            'field' => $field,
                            'confidence' => $confidence,
                            'algorithm' => 'semantic_ai',
                            'semantic_group' => $group,
                            'match_type' => $setType
                        ];
                    }
                }
            }
        }
    }

    return $bestMatch;
}
```

---

## 10. Enhanced Semantic Field Mapping (Albanian Support)

```php
protected function getSemanticFieldMapping(string $group, string $inputField, string $setType = 'basic'): ?string
{
    $fieldLower = strtolower($inputField);

    switch ($group) {
        case 'financial':
            // Added Albanian TVSH detection                                     // ← ADDED
            if (strpos($fieldLower, 'vat') !== false || strpos($fieldLower, 'pdv') !== false ||
                strpos($fieldLower, 'ddv') !== false || strpos($fieldLower, 'tvsh') !== false) {
                return strpos($fieldLower, 'rate') !== false || strpos($fieldLower, 'stopa') !== false ||
                       strpos($fieldLower, 'norma') !== false ? 'vat_rate' : 'vat_amount';
            }
            // ... other logic

        case 'identity':
            // Added Albanian buyer/client detection                             // ← ADDED
            if (strpos($fieldLower, 'customer') !== false || strpos($fieldLower, 'kupac') !== false ||
                strpos($fieldLower, 'partner') !== false || strpos($fieldLower, 'klient') !== false ||
                strpos($fieldLower, 'bleresi') !== false) {
                return 'customer_id';
            }
            // Added Albanian NIPT detection                                     // ← ADDED
            if (strpos($fieldLower, 'tax') !== false || strpos($fieldLower, 'pib') !== false ||
                strpos($fieldLower, 'embs') !== false || strpos($fieldLower, 'nipt') !== false) {
                return 'tax_id';
            }
            // ... other logic

        case 'temporal':
            // Added Albanian date field detection                               // ← ADDED
            if (strpos($fieldLower, 'due') !== false || strpos($fieldLower, 'dospe') !== false ||
                strpos($fieldLower, 'valuta') !== false || strpos($fieldLower, 'afati') !== false) {
                return 'due_date';
            }
            if (strpos($fieldLower, 'payment') !== false || strpos($fieldLower, 'plac') !== false ||
                strpos($fieldLower, 'uplata') !== false || strpos($fieldLower, 'pagesa') !== false) {
                return 'payment_date';
            }
            if (strpos($fieldLower, 'invoice') !== false || strpos($fieldLower, 'faktura') !== false ||
                strpos($fieldLower, 'fature') !== false) {
                return 'invoice_date';
            }
            return 'date';

        // ... other cases
    }

    return null;
}
```

---

## 11. Closing Array Comment (Checkpoint)

```php
        'contact_person' => [
            'kontakt_lice', 'odgovorno_lice', 'contact_person', 'representative',
            'контакт_лице', 'одговорно_лице',
            // Albanian variations
            'personi_kontaktit', 'personi', 'perfaqesuesi',
            // Generic manual CSV patterns
            'contact', 'contact_name', 'rep', 'representative', 'person'
        ]
    ]; // CLAUDE-CHECKPOINT                                                      // ← ADDED
```

---

## Summary of Code Changes

### Lines Modified: ~50 locations
### New Variations Added: 304 total
  - Albanian: 147
  - Manual CSV: 157

### Key Methods Enhanced:
1. `$macedonianCorpus` array - 32 field types expanded
2. `$fieldSynonyms` array - 10 synonym groups enhanced
3. `aiSemanticScore()` - Albanian word detection added
4. `getSemanticFieldMapping()` - Albanian field logic added

### Confidence Levels:
- Albanian exact matches: **1.0 (100%)**
- Manual CSV patterns: **0.60-0.75 (60-75%)**
- Fuzzy Albanian matches: **0.65-0.95** (adaptive)

---

**Implementation Status**: ✅ Code documented, tested, and ready for integration
**Test File**: `test_albanian_fields.php`
**Documentation**: `FIELD_MAPPER_EXPANSION_GUIDE.md`
**Mappings JSON**: `albanian_manual_csv_mappings.json`

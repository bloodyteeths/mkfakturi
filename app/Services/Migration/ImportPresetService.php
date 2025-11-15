<?php

namespace App\Services\Migration;

/**
 * Import Preset Service
 *
 * Provides column mapping presets for different accounting software:
 * - Onivo (Macedonian software)
 * - Megasoft (Macedonian software)
 * - Effect Plus (Macedonian software)
 * - Eurofaktura (Serbian software)
 * - Manager.io (International software)
 * - Generic/Manual (Fallback for non-software CSVs)
 *
 * Maps column names from various sources to our internal field names.
 *
 * @package App\Services\Migration
 */
class ImportPresetService
{
    /**
     * Get preset mapping for a specific source and entity type
     *
     * @param string $source Source system (onivo, megasoft, effectplus, eurofaktura, managerio, generic)
     * @param string $entityType Entity type (customers, items, invoices, bills)
     * @return array Column mapping
     */
    public function getPreset(string $source, string $entityType): array
    {
        return match (strtolower($source)) {
            'onivo' => $this->getOnivoPreset($entityType),
            'megasoft' => $this->getMegasoftPreset($entityType),
            'effectplus' => $this->getEffectPlusPreset($entityType),
            'eurofaktura' => $this->getEurofakturaPreset($entityType),
            'managerio' => $this->getManagerIoPreset($entityType),
            'generic' => $this->getGenericPreset($entityType),
            default => [],
        };
    }

    /**
     * Get all available sources
     *
     * @return array
     */
    public function getAvailableSources(): array
    {
        return [
            'onivo' => 'Onivo',
            'megasoft' => 'Megasoft',
            'effectplus' => 'Effect Plus',
            'eurofaktura' => 'Eurofaktura',
            'managerio' => 'Manager.io',
            'generic' => 'Generic/Manual',
        ];
    }

    /**
     * Get all available entity types
     *
     * @return array
     */
    public function getAvailableEntityTypes(): array
    {
        return [
            'customers' => 'Customers',
            'items' => 'Items',
            'invoices' => 'Invoices',
            'bills' => 'Bills',
        ];
    }

    /**
     * Get Onivo preset mapping
     *
     * Onivo uses Macedonian/Cyrillic column names
     *
     * @param string $entityType
     * @return array
     */
    private function getOnivoPreset(string $entityType): array
    {
        return match (strtolower($entityType)) {
            'customers' => [
                'name' => 'Партнер',
                'email' => 'Email',
                'phone' => 'Телефон',
                'vat_number' => 'ЕДБ',
                'contact_name' => 'Контакт',
                'website' => 'Веб-страна',
            ],
            'items' => [
                'name' => 'Производ',
                'description' => 'Опис',
                'price' => 'Цена',
                'unit_name' => 'Единица',
            ],
            'invoices' => [
                'invoice_number' => 'Број на фактура',
                'customer_name' => 'Купувач',
                'invoice_date' => 'Датум на фактура',
                'due_date' => 'Рок на плаќање',
                'sub_total' => 'Основица',
                'tax' => 'ДДВ',
                'total' => 'Вкупно',
                'discount' => 'Попуст %',
                'discount_val' => 'Попуст износ',
                'notes' => 'Забелешки',
                'status' => 'Статус',
            ],
            default => [],
        };
    }

    /**
     * Get Megasoft preset mapping
     *
     * Megasoft uses mixed Latin/Cyrillic column names
     *
     * @param string $entityType
     * @return array
     */
    private function getMegasoftPreset(string $entityType): array
    {
        return match (strtolower($entityType)) {
            'customers' => [
                'name' => 'ParnerName',
                'email' => 'ParnerEmail',
                'phone' => 'ParnerTel',
                'vat_number' => 'ParnerEDB',
                'contact_name' => 'Kontakt',
                'website' => 'Website',
            ],
            'items' => [
                'name' => 'ArtikalNaziv',
                'description' => 'ArtikalOpis',
                'price' => 'ArtikalCena',
                'unit_name' => 'MernaEdinica',
            ],
            'invoices' => [
                'invoice_number' => 'FakturaBroj',
                'customer_name' => 'Kupuvac',
                'invoice_date' => 'FakturaDatum',
                'due_date' => 'RokNaPlakanje',
                'sub_total' => 'Osnovica',
                'tax' => 'DDV',
                'total' => 'Vkupno',
                'discount' => 'Popust',
                'discount_val' => 'PopustIznos',
                'notes' => 'Zabeleska',
                'status' => 'Status',
            ],
            default => [],
        };
    }

    /**
     * Get Effect Plus preset mapping
     *
     * Effect Plus uses Latin-based Macedonian/Serbian column names
     *
     * @param string $entityType
     * @return array
     */
    private function getEffectPlusPreset(string $entityType): array
    {
        return match (strtolower($entityType)) {
            'customers' => [
                'name' => 'klijent_pun_naziv',
                'contact_name' => 'firma',
                'vat_number' => 'pdv_br',
                'tax_id' => 'mbr',
                'email' => 'email',
                'phone' => 'telefon',
                'address_street_1' => 'adresa',
                'city' => 'grad',
                'zip' => 'postanski_kod',
                'country' => 'drzava',
                'website' => 'web',
            ],
            'items' => [
                'name' => 'artikal',
                'description' => 'opis',
                'unit_name' => 'jm',
                'price' => 'vpc',
                'sale_price' => 'mpc',
                'sku' => 'sifra',
                'quantity' => 'kol',
            ],
            'invoices' => [
                'invoice_number' => 'dok_br',
                'reference_number' => 'fakt_br',
                'invoice_date' => 'dat_izdavanja',
                'due_date' => 'rok_placanja',
                'customer_name' => 'klijent',
                'sub_total' => 'osnovica',
                'tax' => 'porez',
                'discount' => 'rabat',
                'discount_val' => 'rabat_iznos',
                'total' => 'ukupno',
                'paid_status' => 'placeno',
                'notes' => 'napomena',
            ],
            default => [],
        };
    }

    /**
     * Get Eurofaktura preset mapping
     *
     * Eurofaktura uses English-style column names
     *
     * @param string $entityType
     * @return array
     */
    private function getEurofakturaPreset(string $entityType): array
    {
        return match (strtolower($entityType)) {
            'customers' => [
                'name' => 'company_name',
                'contact_name' => 'legal_name',
                'vat_number' => 'tin',
                'tax_id' => 'business_id',
                'email' => 'email',
                'phone' => 'phone',
                'address_street_1' => 'address',
                'address_street_2' => 'address_line_2',
                'city' => 'city',
                'zip' => 'postal_code',
                'country' => 'country',
                'website' => 'website',
            ],
            'items' => [
                'name' => 'line_item',
                'description' => 'description',
                'sku' => 'product_code',
                'unit_name' => 'unit',
                'price' => 'rate',
                'quantity' => 'qty',
            ],
            'invoices' => [
                'invoice_number' => 'invoice_ref',
                'invoice_date' => 'issued_on',
                'due_date' => 'due_on',
                'invoice_type' => 'invoice_type',
                'customer_name' => 'client',
                'sub_total' => 'net_amount',
                'tax' => 'vat',
                'tax_percent' => 'tax_rate',
                'total' => 'gross_amount',
                'discount' => 'discount_percent',
                'discount_val' => 'discount_amount',
                'notes' => 'notes',
                'paid_status' => 'payment_status',
            ],
            default => [],
        };
    }

    /**
     * Get Manager.io preset mapping
     *
     * Manager.io uses English column names with underscores
     *
     * @param string $entityType
     * @return array
     */
    private function getManagerIoPreset(string $entityType): array
    {
        return match (strtolower($entityType)) {
            'customers' => [
                'name' => 'customer',
                'contact_name' => 'business_name',
                'vat_number' => 'tax_number',
                'tax_id' => 'code',
                'email' => 'email',
                'phone' => 'phone',
                'address_street_1' => 'billing_address',
                'city' => 'city',
                'zip' => 'postal_code',
                'state' => 'state',
                'country' => 'country',
            ],
            'items' => [
                'name' => 'item',
                'description' => 'inventory_item',
                'sku' => 'code',
                'unit_name' => 'unit',
                'price' => 'unit_price',
                'quantity' => 'qty',
            ],
            'invoices' => [
                'invoice_number' => 'reference',
                'invoice_date' => 'issue_date',
                'due_date' => 'due_date',
                'customer_name' => 'customer',
                'sub_total' => 'subtotal',
                'tax' => 'tax',
                'total' => 'total',
                'paid_status' => 'amount_paid',
                'balance_due' => 'balance',
                'notes' => 'notes',
                'status' => 'status',
            ],
            default => [],
        };
    }

    /**
     * Get Generic/Manual preset mapping
     *
     * Generic preset with common English field names for manually created CSVs
     *
     * @param string $entityType
     * @return array
     */
    private function getGenericPreset(string $entityType): array
    {
        return match (strtolower($entityType)) {
            'customers' => [
                'name' => 'name',
                'company_name' => 'company',
                'contact_name' => 'contact',
                'email' => 'email',
                'phone' => 'phone',
                'vat_number' => 'vat',
                'tax_id' => 'tax_id',
                'address_street_1' => 'address',
                'city' => 'city',
                'zip' => 'zip',
                'state' => 'state',
                'country' => 'country',
                'website' => 'website',
            ],
            'items' => [
                'name' => 'name',
                'description' => 'description',
                'sku' => 'sku',
                'price' => 'price',
                'unit_name' => 'unit',
                'quantity' => 'quantity',
            ],
            'invoices' => [
                'invoice_number' => 'number',
                'invoice_date' => 'date',
                'due_date' => 'due_date',
                'customer_name' => 'customer',
                'sub_total' => 'subtotal',
                'tax' => 'tax',
                'total' => 'total',
                'discount' => 'discount',
                'notes' => 'notes',
                'status' => 'status',
            ],
            'bills' => [
                'bill_number' => 'bill_number',
                'supplier_name' => 'supplier_name',
                'supplier_tax_id' => 'supplier_tax_id',
                'bill_date' => 'bill_date',
                'due_date' => 'due_date',
                'sub_total' => 'sub_total',
                'tax' => 'tax',
                'total' => 'total',
                'notes' => 'notes',
                'item_description' => 'item_description',
                'item_quantity' => 'item_quantity',
                'item_price' => 'item_price',
            ],
            default => [],
        };
    }

    /**
     * Detect encoding of file content
     *
     * @param string $content
     * @return string Detected encoding (UTF-8, Windows-1251, etc.)
     */
    public function detectEncoding(string $content): string
    {
        // Try to detect encoding
        $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'ISO-8859-1', 'ASCII'], true);

        return $encoding ?: 'UTF-8';
    }

    /**
     * Convert file content to UTF-8
     *
     * @param string $content
     * @param string $fromEncoding
     * @return string
     */
    public function convertToUtf8(string $content, string $fromEncoding): string
    {
        if ($fromEncoding === 'UTF-8') {
            return $content;
        }

        return mb_convert_encoding($content, 'UTF-8', $fromEncoding);
    }

    /**
     * Detect CSV delimiter
     *
     * @param string $content First few lines of CSV
     * @return string Detected delimiter
     */
    public function detectDelimiter(string $content): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $counts = [];

        foreach ($delimiters as $delimiter) {
            $lines = explode("\n", $content, 3);
            $firstLine = $lines[0] ?? '';
            $counts[$delimiter] = substr_count($firstLine, $delimiter);
        }

        arsort($counts);

        return array_key_first($counts) ?: ',';
    }

    /**
     * Get preset structure for frontend
     *
     * @param string $source
     * @param string $entityType
     * @return array
     */
    public function getPresetStructure(string $source, string $entityType): array
    {
        $mapping = $this->getPreset($source, $entityType);

        return [
            'source' => $source,
            'entity_type' => $entityType,
            'mapping' => $mapping,
            'fields' => array_keys($mapping),
            'columns' => array_values($mapping),
        ];
    }
}

// CLAUDE-CHECKPOINT

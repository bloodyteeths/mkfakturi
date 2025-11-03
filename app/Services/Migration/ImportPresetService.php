<?php

namespace App\Services\Migration;

/**
 * Import Preset Service
 *
 * Provides column mapping presets for different accounting software:
 * - Onivo (Macedonian software)
 * - Megasoft (Macedonian software)
 *
 * Maps Macedonian/Cyrillic column names to our internal field names.
 *
 * @package App\Services\Migration
 */
class ImportPresetService
{
    /**
     * Get preset mapping for a specific source and entity type
     *
     * @param string $source Source system (onivo, megasoft)
     * @param string $entityType Entity type (customers, items, invoices)
     * @return array Column mapping
     */
    public function getPreset(string $source, string $entityType): array
    {
        return match (strtolower($source)) {
            'onivo' => $this->getOnivoPreset($entityType),
            'megasoft' => $this->getMegasoftPreset($entityType),
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

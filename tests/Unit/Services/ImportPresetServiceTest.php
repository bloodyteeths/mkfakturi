<?php

namespace Tests\Unit\Services;

use App\Services\Migration\ImportPresetService;
use Tests\TestCase;

/**
 * Unit tests for ImportPresetService
 *
 * @package Tests\Unit\Services
 */
class ImportPresetServiceTest extends TestCase
{
    private ImportPresetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImportPresetService();
    }

    public function test_onivo_preset_structure()
    {
        $preset = $this->service->getPreset('onivo', 'customers');

        $this->assertIsArray($preset);
        $this->assertArrayHasKey('name', $preset);
        $this->assertArrayHasKey('email', $preset);
        $this->assertArrayHasKey('phone', $preset);
        $this->assertArrayHasKey('vat_number', $preset);

        // Check Macedonian/Cyrillic column names
        $this->assertEquals('Партнер', $preset['name']);
        $this->assertEquals('Email', $preset['email']);
        $this->assertEquals('Телефон', $preset['phone']);
        $this->assertEquals('ЕДБ', $preset['vat_number']);
    }

    public function test_megasoft_preset_structure()
    {
        $preset = $this->service->getPreset('megasoft', 'customers');

        $this->assertIsArray($preset);
        $this->assertArrayHasKey('name', $preset);
        $this->assertArrayHasKey('email', $preset);
        $this->assertArrayHasKey('phone', $preset);
        $this->assertArrayHasKey('vat_number', $preset);

        // Check mixed Latin/Cyrillic column names
        $this->assertEquals('ParnerName', $preset['name']);
        $this->assertEquals('ParnerEmail', $preset['email']);
        $this->assertEquals('ParnerTel', $preset['phone']);
        $this->assertEquals('ParnerEDB', $preset['vat_number']);
    }

    public function test_onivo_items_preset()
    {
        $preset = $this->service->getPreset('onivo', 'items');

        $this->assertIsArray($preset);
        $this->assertArrayHasKey('name', $preset);
        $this->assertArrayHasKey('description', $preset);
        $this->assertArrayHasKey('price', $preset);
        $this->assertArrayHasKey('unit_name', $preset);

        $this->assertEquals('Производ', $preset['name']);
        $this->assertEquals('Опис', $preset['description']);
        $this->assertEquals('Цена', $preset['price']);
        $this->assertEquals('Единица', $preset['unit_name']);
    }

    public function test_megasoft_items_preset()
    {
        $preset = $this->service->getPreset('megasoft', 'items');

        $this->assertIsArray($preset);
        $this->assertArrayHasKey('name', $preset);
        $this->assertArrayHasKey('description', $preset);
        $this->assertArrayHasKey('price', $preset);
        $this->assertArrayHasKey('unit_name', $preset);

        $this->assertEquals('ArtikalNaziv', $preset['name']);
        $this->assertEquals('ArtikalOpis', $preset['description']);
        $this->assertEquals('ArtikalCena', $preset['price']);
        $this->assertEquals('MernaEdinica', $preset['unit_name']);
    }

    public function test_onivo_invoices_preset()
    {
        $preset = $this->service->getPreset('onivo', 'invoices');

        $this->assertIsArray($preset);
        $this->assertArrayHasKey('invoice_number', $preset);
        $this->assertArrayHasKey('customer_name', $preset);
        $this->assertArrayHasKey('invoice_date', $preset);
        $this->assertArrayHasKey('total', $preset);

        $this->assertEquals('Број на фактура', $preset['invoice_number']);
        $this->assertEquals('Купувач', $preset['customer_name']);
        $this->assertEquals('Датум на фактура', $preset['invoice_date']);
        $this->assertEquals('Вкупно', $preset['total']);
    }

    public function test_megasoft_invoices_preset()
    {
        $preset = $this->service->getPreset('megasoft', 'invoices');

        $this->assertIsArray($preset);
        $this->assertArrayHasKey('invoice_number', $preset);
        $this->assertArrayHasKey('customer_name', $preset);
        $this->assertArrayHasKey('invoice_date', $preset);
        $this->assertArrayHasKey('total', $preset);

        $this->assertEquals('FakturaBroj', $preset['invoice_number']);
        $this->assertEquals('Kupuvac', $preset['customer_name']);
        $this->assertEquals('FakturaDatum', $preset['invoice_date']);
        $this->assertEquals('Vkupno', $preset['total']);
    }

    public function test_unknown_source_returns_empty_array()
    {
        $preset = $this->service->getPreset('unknown', 'customers');
        $this->assertIsArray($preset);
        $this->assertEmpty($preset);
    }

    public function test_unknown_entity_type_returns_empty_array()
    {
        $preset = $this->service->getPreset('onivo', 'unknown');
        $this->assertIsArray($preset);
        $this->assertEmpty($preset);
    }

    public function test_get_available_sources()
    {
        $sources = $this->service->getAvailableSources();

        $this->assertIsArray($sources);
        $this->assertArrayHasKey('onivo', $sources);
        $this->assertArrayHasKey('megasoft', $sources);
        $this->assertEquals('Onivo', $sources['onivo']);
        $this->assertEquals('Megasoft', $sources['megasoft']);
    }

    public function test_get_available_entity_types()
    {
        $entityTypes = $this->service->getAvailableEntityTypes();

        $this->assertIsArray($entityTypes);
        $this->assertArrayHasKey('customers', $entityTypes);
        $this->assertArrayHasKey('items', $entityTypes);
        $this->assertArrayHasKey('invoices', $entityTypes);
    }

    public function test_detect_delimiter()
    {
        $csvContent = "name,email,phone\nJohn,john@example.com,123456\n";
        $delimiter = $this->service->detectDelimiter($csvContent);
        $this->assertEquals(',', $delimiter);

        $csvContent = "name;email;phone\nJohn;john@example.com;123456\n";
        $delimiter = $this->service->detectDelimiter($csvContent);
        $this->assertEquals(';', $delimiter);

        $csvContent = "name\temail\tphone\nJohn\tjohn@example.com\t123456\n";
        $delimiter = $this->service->detectDelimiter($csvContent);
        $this->assertEquals("\t", $delimiter);
    }

    public function test_detect_encoding()
    {
        // UTF-8 content
        $content = "name,email\nЈован,jovan@example.com\n";
        $encoding = $this->service->detectEncoding($content);
        $this->assertEquals('UTF-8', $encoding);
    }

    public function test_get_preset_structure()
    {
        $structure = $this->service->getPresetStructure('onivo', 'customers');

        $this->assertIsArray($structure);
        $this->assertArrayHasKey('source', $structure);
        $this->assertArrayHasKey('entity_type', $structure);
        $this->assertArrayHasKey('mapping', $structure);
        $this->assertArrayHasKey('fields', $structure);
        $this->assertArrayHasKey('columns', $structure);

        $this->assertEquals('onivo', $structure['source']);
        $this->assertEquals('customers', $structure['entity_type']);
        $this->assertIsArray($structure['mapping']);
        $this->assertIsArray($structure['fields']);
        $this->assertIsArray($structure['columns']);
    }
}

// CLAUDE-CHECKPOINT

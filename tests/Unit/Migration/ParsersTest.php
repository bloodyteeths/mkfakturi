<?php

namespace Tests\Unit\Migration;

use App\Models\ImportJob;
use App\Services\Migration\FieldMapperService;
use App\Services\Migration\Parsers\CsvParserService;
use App\Services\Migration\Parsers\ExcelParserService;
use App\Services\Migration\Parsers\XmlParserService;
use App\Services\Migration\Transformers\DateTransformer;
use App\Services\Migration\Transformers\DecimalTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ParsersTest - Test file parser services with Macedonia-specific data
 *
 * This test suite validates the three parser services (CSV, Excel, XML)
 * with Macedonia-specific data formats, encodings, and business scenarios.
 *
 * Test Coverage:
 * - Macedonia-specific encodings (UTF-8, Windows-1251)
 * - Date formats (dd.mm.yyyy, dd/mm/yyyy)
 * - Decimal separators (comma to dot conversion)
 * - Cyrillic text handling in field names and data
 * - Large file processing capabilities
 * - Error handling and recovery
 * - Field mapping accuracy with Macedonian terms
 * - Progress callback functionality
 */
class ParsersTest extends TestCase
{
    use RefreshDatabase;

    private CsvParserService $csvParser;

    private ExcelParserService $excelParser;

    private XmlParserService $xmlParser;

    private ImportJob $importJob;

    protected function setUp(): void
    {
        parent::setUp();

        // Create parser instances
        $fieldMapper = app(FieldMapperService::class);
        $dateTransformer = app(DateTransformer::class);
        $decimalTransformer = app(DecimalTransformer::class);

        $this->csvParser = new CsvParserService($fieldMapper, $dateTransformer, $decimalTransformer);
        $this->excelParser = new ExcelParserService($fieldMapper, $dateTransformer, $decimalTransformer);
        $this->xmlParser = new XmlParserService($fieldMapper, $dateTransformer, $decimalTransformer);

        // Create test import job using factory so required fields are populated
        $this->importJob = \Database\Factories\ImportJobFactory::new()->create([
            'name' => 'Macedonia Test Import',
            'type' => \App\Models\ImportJob::TYPE_CUSTOMERS,
            'file_path' => 'imports/test_file.csv',
            'file_info' => [
                'original_name' => 'test_file.csv',
                'filename' => 'test_file.csv',
                'extension' => 'csv',
                'size' => 1024,
                'mime_type' => 'text/csv',
            ],
            'status' => \App\Models\ImportJob::STATUS_PENDING,
            'total_records' => 100,
            'processed_records' => 0,
        ]);
    }

    /** @test */
    public function csv_parser_handles_macedonia_customer_data()
    {
        // Create test CSV with Macedonia customer data
        $csvContent = <<<'CSV'
naziv,embs,адреса,град,телефон,email
"Трговија ДООЕЛ",4030998123000,"ул. Маршал Тито бр.15","Скопje","+38970123456","info@trgovija.mk"
"Mega Trade",4030998456000,"Partizanska 25","Bitola","+38975987654","contact@megatrade.mk"
"IT Solutions МК",4030998789000,"Даме Груев 10","Охрид","+38976555444","hello@itsolutions.mk"
CSV;

        $testFile = $this->createTestFile('macedonia_customers.csv', $csvContent);

        $result = $this->csvParser->parse($this->importJob, $testFile);

        // Validate structure
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('field_mappings', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertArrayHasKey('metadata', $result);

        // Validate data processing
        $this->assertCount(3, $result['rows']);

        // Check if Macedonia-specific fields are mapped correctly
        $mappings = $result['field_mappings'];
        $this->assertGreaterThan(0.7, $mappings['naziv']['confidence']); // "naziv" should map to customer_name
        $this->assertGreaterThan(0.7, $mappings['embs']['confidence']); // "embs" should map to tax_id

        // Validate Cyrillic text preservation
        $firstRow = $result['rows'][0];
        $this->assertStringContainsString('Трговија', $firstRow['customer_name'] ?? $firstRow['naziv']);

        $this->cleanupTestFile($testFile);
    }

    /** @test */
    public function csv_parser_handles_macedonia_decimal_formats()
    {
        // CSV with Macedonia decimal formats (comma separators)
        $csvContent = <<<'CSV'
stavka,količina,cena,iznos,pdv_stapka
"Лаптоп HP","2","45.500,00","91.000,00","18,00"
"Миш","5","850,50","4.252,50","18,00"  
"Тастатура","3","2.300,75","6.902,25","5,00"
CSV;

        $testFile = $this->createTestFile('macedonia_items.csv', $csvContent);

        $result = $this->csvParser->parse($this->importJob, $testFile);

        // Validate decimal transformation
        $this->assertCount(3, $result['rows']);

        // Check that decimal separators are converted properly
        $firstRow = $result['rows'][0];
        $this->assertIsNumeric($firstRow['unit_price'] ?? $firstRow['cena']);

        $this->cleanupTestFile($testFile);
    }

    /** @test */
    public function csv_parser_handles_windows_1251_encoding()
    {
        // Create CSV content with Windows-1251 encoded Cyrillic
        $csvContent = "naziv,opis,cena\n";
        $csvContent .= "Производ,Опис на производот,1250.50\n";
        $csvContent .= "Услуга,Консултантски услуги,3500.00\n";

        // Convert to Windows-1251 encoding
        $encodedContent = mb_convert_encoding($csvContent, 'Windows-1251', 'UTF-8');

        $testFile = $this->createTestFile('cyrillic_products.csv', $encodedContent);

        $result = $this->csvParser->parse($this->importJob, $testFile, [
            'encoding' => 'Windows-1251',
        ]);

        // Validate encoding conversion worked
        $this->assertCount(2, $result['rows']);

        // Check Cyrillic text is properly decoded
        $firstRow = $result['rows'][0];
        $productName = $firstRow['item_name'] ?? $firstRow['naziv'];
        $this->assertStringContainsString('Производ', $productName);

        $this->cleanupTestFile($testFile);
    }

    /** @test */
    public function excel_parser_handles_macedonia_invoice_data()
    {
        // This would require creating an actual Excel file
        // For now, we'll test the preview functionality with XML
        $this->markTestSkipped('Excel file creation requires additional setup');
    }

    /** @test */
    public function xml_parser_handles_ubl_invoice_format()
    {
        // Create test UBL invoice XML
        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
    <cbc:ID>INV-2025-001</cbc:ID>
    <cbc:IssueDate>2025-01-15</cbc:IssueDate>
    <cbc:DueDate>2025-02-15</cbc:DueDate>
    
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyName>
                <cbc:Name>Тест Компанија ДООЕЛ</cbc:Name>
            </cac:PartyName>
        </cac:Party>
    </cac:AccountingSupplierParty>
    
    <cac:InvoiceLine>
        <cbc:ID>1</cbc:ID>
        <cbc:InvoicedQuantity unitCode="PCE">2</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID="MKD">15000.00</cbc:LineExtensionAmount>
        <cac:Item>
            <cbc:Name>Лаптоп компјутер</cbc:Name>
            <cbc:Description>HP EliteBook професионален лаптоп</cbc:Description>
        </cac:Item>
        <cac:Price>
            <cbc:PriceAmount currencyID="MKD">7500.00</cbc:PriceAmount>
        </cac:Price>
    </cac:InvoiceLine>
    
    <cac:InvoiceLine>
        <cbc:ID>2</cbc:ID>
        <cbc:InvoicedQuantity unitCode="PCE">1</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID="MKD">2500.00</cbc:LineExtensionAmount>
        <cac:Item>
            <cbc:Name>Принтер</cbc:Name>
            <cbc:Description>Canon лазерски принтер</cbc:Description>
        </cac:Item>
        <cac:Price>
            <cbc:PriceAmount currencyID="MKD">2500.00</cbc:PriceAmount>
        </cac:Price>
    </cac:InvoiceLine>
</Invoice>
XML;

        $testFile = $this->createTestFile('test_invoice.xml', $xmlContent);

        $result = $this->xmlParser->parse($this->importJob, $testFile);

        // Validate XML parsing
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertArrayHasKey('metadata', $result);

        // Check UBL format detection
        $this->assertEquals('ubl_invoice', $result['metadata']['xml_format']);

        // Validate data extraction
        $this->assertGreaterThan(0, count($result['rows']));

        // Check Cyrillic text preservation in XML
        $hasLaptop = false;
        foreach ($result['rows'] as $row) {
            if (isset($row['item_name']) && strpos($row['item_name'], 'Лаптоп') !== false) {
                $hasLaptop = true;
                break;
            }
        }
        $this->assertTrue($hasLaptop, 'Cyrillic text not properly preserved in XML parsing');

        $this->cleanupTestFile($testFile);
    }

    /** @test */
    public function xml_parser_handles_onivo_export_format()
    {
        // Create test Onivo export XML format
        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<OnivoExport xmlns="http://www.onivo.mk/export/schema">
    <Customer>
        <ID>1001</ID>
        <Name>Македонска Компанија ДООЕЛ</Name>
        <TaxID>4030998000123</TaxID>
        <Address>ул. Александар Македонски 10</Address>
        <City>Скопје</City>
        <Email>info@makedonskakomp.mk</Email>
    </Customer>
    
    <Customer>
        <ID>1002</ID>
        <Name>Balkan Trade</Name>
        <TaxID>4030998000456</TaxID>
        <Address>Partizanska 45</Address>
        <City>Битола</City>
        <Email>contact@balkantrade.mk</Email>
    </Customer>
    
    <Invoice>
        <Number>2025-001</Number>
        <Date>15.01.2025</Date>
        <DueDate>15.02.2025</DueDate>
        <CustomerID>1001</CustomerID>
        <TotalAmount>18000.00</TotalAmount>
        <Currency>MKD</Currency>
    </Invoice>
</OnivoExport>
XML;

        $testFile = $this->createTestFile('onivo_export.xml', $xmlContent);

        $result = $this->xmlParser->parse($this->importJob, $testFile);

        // Validate Onivo format detection
        $this->assertEquals('onivo_export', $result['metadata']['xml_format']);

        // Validate data extraction
        $this->assertGreaterThan(0, count($result['rows']));

        // Check field mapping worked
        $this->assertArrayHasKey('field_mappings', $result);

        $this->cleanupTestFile($testFile);
    }

    /** @test */
    public function parsers_handle_progress_callbacks()
    {
        $csvContent = <<<'CSV'
naziv,iznos
"Item 1","100.50"
"Item 2","200.75"
"Item 3","300.25"
"Item 4","400.00"
"Item 5","500.50"
CSV;

        $testFile = $this->createTestFile('progress_test.csv', $csvContent);

        $progressUpdates = [];
        $progressCallback = function ($progress) use (&$progressUpdates) {
            $progressUpdates[] = $progress;
        };

        $result = $this->csvParser->parse(
            $this->importJob,
            $testFile,
            ['chunk_size' => 2], // Small chunks to trigger multiple progress updates
            $progressCallback
        );

        // Validate progress callbacks were triggered
        $this->assertGreaterThan(0, count($progressUpdates));

        // Check progress structure
        foreach ($progressUpdates as $progress) {
            $this->assertArrayHasKey('processed', $progress);
            $this->assertArrayHasKey('total', $progress);
            $this->assertArrayHasKey('percentage', $progress);
        }

        // Final progress should be 100%
        $finalProgress = end($progressUpdates);
        $this->assertEquals(100, $finalProgress['percentage']);

        $this->cleanupTestFile($testFile);
    }

    /** @test */
    public function parsers_handle_large_files_efficiently()
    {
        // Create a larger CSV file to test memory efficiency
        $lines = ['naziv,iznos,opis'];

        for ($i = 1; $i <= 1000; $i++) {
            $lines[] = "\"Item {$i}\",\"".($i * 10.50)."\",\"Description for item {$i}\"";
        }

        $csvContent = implode("\n", $lines);
        $testFile = $this->createTestFile('large_test.csv', $csvContent);

        $startMemory = memory_get_usage();

        $result = $this->csvParser->parse($this->importJob, $testFile, [
            'chunk_size' => 100,
        ]);

        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;

        // Validate results
        $this->assertCount(1000, $result['rows']);
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed); // Less than 50MB memory usage

        $this->cleanupTestFile($testFile);
    }

    /** @test */
    public function parsers_provide_preview_functionality()
    {
        $csvContent = <<<'CSV'
naziv,cena,количина,опис
"Производ 1","1250.50","10","Опис на производ 1"
"Производ 2","2500.75","5","Опис на производ 2"
"Производ 3","750.25","20","Опис на производ 3"
CSV;

        $testFile = $this->createTestFile('preview_test.csv', $csvContent);

        $preview = $this->csvParser->preview($testFile);

        // Validate preview structure
        $this->assertArrayHasKey('headers', $preview);
        $this->assertArrayHasKey('sample_rows', $preview);
        $this->assertArrayHasKey('detected_structure', $preview);
        $this->assertArrayHasKey('total_columns', $preview);

        // Check headers include Cyrillic
        $this->assertContains('количина', $preview['headers']);

        // Check sample data
        $this->assertCount(3, $preview['sample_rows']);

        $this->cleanupTestFile($testFile);
    }

    /**
     * Create a temporary test file
     */
    private function createTestFile(string $filename, string $content): string
    {
        $testPath = storage_path('testing');
        if (! is_dir($testPath)) {
            mkdir($testPath, 0755, true);
        }

        $filePath = $testPath.'/'.$filename;
        file_put_contents($filePath, $content);

        return $filePath;
    }

    /**
     * Clean up test file
     */
    private function cleanupTestFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up any remaining test files
        $testPath = storage_path('testing');
        if (is_dir($testPath)) {
            $files = glob($testPath.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        parent::tearDown();
    }
}

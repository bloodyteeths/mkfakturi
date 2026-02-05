<?php

namespace Tests\Unit\Services\Banking;

use App\Services\Banking\Parsers\CsvParserFactory;
use App\Services\Banking\Parsers\GenericCsvParser;
use App\Services\Banking\Parsers\KomercijalnaCsvParser;
use App\Services\Banking\Parsers\NlbCsvParser;
use App\Services\Banking\Parsers\StopanskaСsvParser;
use PHPUnit\Framework\TestCase;

/**
 * CSV Parser Tests
 *
 * Tests for bank-specific CSV parsers.
 */
class CsvParserTest extends TestCase
{
    /**
     * Test NLB CSV parsing (semicolon delimiter)
     */
    public function test_nlb_parser_parses_semicolon_csv(): void
    {
        $csv = <<<CSV
Датум;Износ;Валута;Опис;Референца;Партнер;Сметка
05.02.2026;15000,00;MKD;Плаќање за фактура INV-001;REF-001;Компанија ДООЕЛ;300000000001234
06.02.2026;-5000,50;MKD;Плаќање на добавувач;REF-002;Добавувач АД;300000000005678
CSV;

        $parser = new NlbCsvParser();

        $this->assertTrue($parser->canParse($csv));
        $this->assertEquals('nlb', $parser->getBankCode());
        $this->assertEquals(';', $parser->getDelimiter());

        $transactions = $parser->parse($csv);

        $this->assertCount(2, $transactions);

        // First transaction (credit)
        $this->assertEquals(15000.00, $transactions[0]['amount']);
        $this->assertEquals('MKD', $transactions[0]['currency']);
        $this->assertStringContains('INV-001', $transactions[0]['description']);
        $this->assertEquals('REF-001', $transactions[0]['reference']);

        // Second transaction (debit)
        $this->assertEquals(-5000.50, $transactions[1]['amount']);
    }

    /**
     * Test NLB with separate credit/debit columns
     */
    public function test_nlb_parser_handles_separate_credit_debit_columns(): void
    {
        $csv = <<<CSV
Датум;Кредит;Дебит;Опис;Референца
05.02.2026;10000,00;;Прилив;REF-001
06.02.2026;;3000,00;Одлив;REF-002
CSV;

        $parser = new NlbCsvParser();
        $transactions = $parser->parse($csv);

        $this->assertCount(2, $transactions);
        $this->assertEquals(10000.00, $transactions[0]['amount']); // Credit is positive
        $this->assertEquals(-3000.00, $transactions[1]['amount']); // Debit is negative
    }

    /**
     * Test Stopanska CSV parsing (comma delimiter)
     */
    public function test_stopanska_parser_parses_comma_csv(): void
    {
        $csv = <<<CSV
Датум,Износ,Валута,Опис,Референца,Партнер
05.02.2026,8500.50,MKD,Incoming payment,REF-101,Client Company
07.02.2026,-2000.00,MKD,Outgoing payment,REF-102,Supplier Ltd
CSV;

        $parser = new StopanskaСsvParser();

        $this->assertTrue($parser->canParse($csv));
        $this->assertEquals('stopanska', $parser->getBankCode());
        $this->assertEquals(',', $parser->getDelimiter());

        $transactions = $parser->parse($csv);

        $this->assertCount(2, $transactions);
        $this->assertEquals(8500.50, $transactions[0]['amount']);
        $this->assertEquals(-2000.00, $transactions[1]['amount']);
    }

    /**
     * Test Komercijalna CSV parsing (tab delimiter)
     */
    public function test_komercijalna_parser_parses_tab_csv(): void
    {
        $csv = "Датум\tЗадолжување\tОдобрување\tОпис\tБрој на документ\tНазив\n";
        $csv .= "05.02.2026\t\t22000,00\tУплата\tDOC-001\tКлиент\n";
        $csv .= "06.02.2026\t7500,00\t\tИсплата\tDOC-002\tДобавувач\n";

        $parser = new KomercijalnaCsvParser();

        $this->assertTrue($parser->canParse($csv));
        $this->assertEquals('komercijalna', $parser->getBankCode());
        $this->assertEquals("\t", $parser->getDelimiter());

        $transactions = $parser->parse($csv);

        $this->assertCount(2, $transactions);
        $this->assertEquals(22000.00, $transactions[0]['amount']); // Credit
        $this->assertEquals(-7500.00, $transactions[1]['amount']); // Debit
    }

    /**
     * Test Generic parser auto-detects delimiter
     */
    public function test_generic_parser_auto_detects_delimiter(): void
    {
        // Semicolon CSV
        $semicolonCsv = "date;amount;description\n2026-02-05;1000;Test payment";
        $parser = new GenericCsvParser();
        $parser->canParse($semicolonCsv); // Triggers detection

        $transactions = $parser->parse($semicolonCsv);
        $this->assertCount(1, $transactions);
        $this->assertEquals(1000.00, $transactions[0]['amount']);

        // Comma CSV
        $commaCsv = "date,amount,description\n2026-02-05,2000,Another payment";
        $parser2 = new GenericCsvParser();

        $transactions2 = $parser2->parse($commaCsv);
        $this->assertCount(1, $transactions2);
        $this->assertEquals(2000.00, $transactions2[0]['amount']);
    }

    /**
     * Test factory creates correct parser by bank code
     */
    public function test_factory_creates_parser_by_bank_code(): void
    {
        $nlbParser = CsvParserFactory::createByBankCode('nlb');
        $this->assertInstanceOf(NlbCsvParser::class, $nlbParser);

        $stopanskaParser = CsvParserFactory::createByBankCode('stopanska');
        $this->assertInstanceOf(StopanskaСsvParser::class, $stopanskaParser);

        $komercijalnaParser = CsvParserFactory::createByBankCode('komercijalna');
        $this->assertInstanceOf(KomercijalnaCsvParser::class, $komercijalnaParser);

        // Unknown bank returns generic
        $unknownParser = CsvParserFactory::createByBankCode('unknown');
        $this->assertInstanceOf(GenericCsvParser::class, $unknownParser);
    }

    /**
     * Test factory auto-detects parser from content
     */
    public function test_factory_auto_detects_parser(): void
    {
        // NLB-style content (semicolon, Macedonian columns)
        $nlbContent = "Датум;Износ;Опис\n05.02.2026;1000;Test";
        $parser = CsvParserFactory::detectParser($nlbContent);
        $this->assertEquals('nlb', $parser->getBankCode());

        // Generic content (no specific markers)
        $genericContent = "date,amount,description\n2026-02-05,1000,Test";
        $parser2 = CsvParserFactory::detectParser($genericContent);
        $this->assertEquals('generic', $parser2->getBankCode());
    }

    /**
     * Test amount parsing handles various formats
     */
    public function test_amount_parsing_handles_various_formats(): void
    {
        $parser = new GenericCsvParser();

        // Test different formats using parse method indirectly
        $testCases = [
            "date,amount\n2026-02-05,1234.56" => 1234.56,       // US format
            "date,amount\n2026-02-05,\"1,234.56\"" => 1234.56,  // US with thousands
            "date,amount\n2026-02-05,\"1.234,56\"" => 1234.56,  // European format
            "date,amount\n2026-02-05,-500" => -500.00,          // Negative
        ];

        foreach ($testCases as $csv => $expected) {
            $transactions = $parser->parse($csv);
            $this->assertEquals($expected, $transactions[0]['amount'], "Failed for: $csv");
        }
    }

    /**
     * Test date parsing handles various formats
     */
    public function test_date_parsing_handles_various_formats(): void
    {
        $parser = new GenericCsvParser();

        $testCases = [
            "date,amount\n05.02.2026,100" => '2026-02-05',  // dd.mm.yyyy
            "date,amount\n2026-02-05,100" => '2026-02-05',  // yyyy-mm-dd
            "date,amount\n05/02/2026,100" => '2026-02-05',  // dd/mm/yyyy
        ];

        foreach ($testCases as $csv => $expectedDate) {
            $transactions = $parser->parse($csv);
            $this->assertEquals(
                $expectedDate,
                $transactions[0]['transaction_date']->format('Y-m-d'),
                "Date parsing failed for: $csv"
            );
        }
    }

    /**
     * Test supported banks list
     */
    public function test_get_supported_banks(): void
    {
        $banks = CsvParserFactory::getSupportedBanks();

        $this->assertNotEmpty($banks);

        $bankCodes = array_column($banks, 'code');
        $this->assertContains('nlb', $bankCodes);
        $this->assertContains('stopanska', $bankCodes);
        $this->assertContains('komercijalna', $bankCodes);

        // Generic should not be in the list
        $this->assertNotContains('generic', $bankCodes);
    }

    /**
     * Test empty CSV returns empty array
     */
    public function test_empty_csv_returns_empty_array(): void
    {
        $parser = new GenericCsvParser();

        $this->assertFalse($parser->canParse(''));
        $this->assertFalse($parser->canParse('   '));
    }

    /**
     * Test parser skips invalid rows
     */
    public function test_parser_skips_invalid_rows(): void
    {
        $csv = <<<CSV
date,amount,description
2026-02-05,1000,Valid row
2026-02-06,0,Zero amount row
2026-02-07,,Empty amount row
2026-02-08,2000,Another valid row
CSV;

        $parser = new GenericCsvParser();
        $transactions = $parser->parse($csv);

        // Should only have 2 valid transactions (non-zero amounts)
        $this->assertCount(2, $transactions);
        $this->assertEquals(1000.00, $transactions[0]['amount']);
        $this->assertEquals(2000.00, $transactions[1]['amount']);
    }

    /**
     * Helper to check if string contains substring
     */
    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            strpos($haystack, $needle) !== false,
            "Failed asserting that '$haystack' contains '$needle'"
        );
    }
}

// CLAUDE-CHECKPOINT

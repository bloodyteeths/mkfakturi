<?php

namespace Tests\Unit\Services\Banking;

use App\Services\Banking\TransactionFingerprint;
use Tests\TestCase;

/**
 * P0-11: Unit tests for TransactionFingerprint service
 *
 * Tests fingerprint generation, normalization, and deduplication correctness.
 */
class TransactionFingerprintTest extends TestCase
{
    protected TransactionFingerprint $fingerprinter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fingerprinter = new TransactionFingerprint;
    }

    // ─── generate() tests ─────────────────────────────────────────────

    /** @test */
    public function test_generate_returns_64_char_sha256_hash(): void
    {
        $tx = $this->sampleTransaction();
        $fingerprint = $this->fingerprinter->generate($tx);

        $this->assertIsString($fingerprint);
        $this->assertEquals(64, strlen($fingerprint));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $fingerprint);
    }

    /** @test */
    public function test_same_transaction_produces_same_fingerprint(): void
    {
        $tx = $this->sampleTransaction();

        $fp1 = $this->fingerprinter->generate($tx);
        $fp2 = $this->fingerprinter->generate($tx);

        $this->assertEquals($fp1, $fp2);
    }

    /** @test */
    public function test_different_amounts_produce_different_fingerprints(): void
    {
        $tx1 = $this->sampleTransaction(['amount' => '1000.00']);
        $tx2 = $this->sampleTransaction(['amount' => '1000.01']);

        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);

        $this->assertNotEquals($fp1, $fp2);
    }

    /** @test */
    public function test_different_dates_produce_different_fingerprints(): void
    {
        $tx1 = $this->sampleTransaction(['transaction_date' => '2025-11-01']);
        $tx2 = $this->sampleTransaction(['transaction_date' => '2025-11-02']);

        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);

        $this->assertNotEquals($fp1, $fp2);
    }

    /** @test */
    public function test_different_companies_produce_different_fingerprints(): void
    {
        $tx1 = $this->sampleTransaction(['company_id' => 1]);
        $tx2 = $this->sampleTransaction(['company_id' => 2]);

        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);

        $this->assertNotEquals($fp1, $fp2);
    }

    /** @test */
    public function test_external_transaction_id_takes_priority(): void
    {
        $tx = $this->sampleTransaction([
            'external_transaction_id' => 'BANK-TX-12345',
        ]);

        $fp = $this->fingerprinter->generate($tx);

        // Should be based on company_id + external_transaction_id only
        $expected = hash('sha256', $tx['company_id'] . '|' . 'BANK-TX-12345');
        $this->assertEquals($expected, $fp);
    }

    /** @test */
    public function test_external_id_fingerprint_ignores_other_fields(): void
    {
        $tx1 = $this->sampleTransaction([
            'external_transaction_id' => 'BANK-TX-12345',
            'amount' => '1000.00',
        ]);
        $tx2 = $this->sampleTransaction([
            'external_transaction_id' => 'BANK-TX-12345',
            'amount' => '9999.99',
            'description' => 'Completely different description',
        ]);

        // Same external ID + same company = same fingerprint
        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);

        $this->assertEquals($fp1, $fp2);
    }

    /** @test */
    public function test_empty_external_id_falls_back_to_composite(): void
    {
        $tx1 = $this->sampleTransaction(['external_transaction_id' => '']);
        $tx2 = $this->sampleTransaction(['external_transaction_id' => null]);
        $tx3 = $this->sampleTransaction(); // no external_transaction_id key

        // All three should produce the same composite fingerprint
        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);
        $fp3 = $this->fingerprinter->generate($tx3);

        $this->assertEquals($fp1, $fp2);
        $this->assertEquals($fp2, $fp3);
    }

    /** @test */
    public function test_whitespace_in_description_is_normalized(): void
    {
        $tx1 = $this->sampleTransaction(['description' => 'Payment from John']);
        $tx2 = $this->sampleTransaction(['description' => '  Payment   from   John  ']);

        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);

        $this->assertEquals($fp1, $fp2);
    }

    /** @test */
    public function test_case_in_description_is_normalized(): void
    {
        $tx1 = $this->sampleTransaction(['description' => 'Payment From JOHN']);
        $tx2 = $this->sampleTransaction(['description' => 'payment from john']);

        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);

        $this->assertEquals($fp1, $fp2);
    }

    /** @test */
    public function test_cyrillic_text_is_normalized_correctly(): void
    {
        $tx1 = $this->sampleTransaction(['description' => 'Плаќање од Јован']);
        $tx2 = $this->sampleTransaction(['description' => 'плаќање од јован']);

        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);

        $this->assertEquals($fp1, $fp2);
    }

    /** @test */
    public function test_description_truncated_to_100_chars(): void
    {
        $shortDesc = str_repeat('a', 100);
        $longDesc = str_repeat('a', 100) . str_repeat('b', 50);

        $tx1 = $this->sampleTransaction(['description' => $shortDesc]);
        $tx2 = $this->sampleTransaction(['description' => $longDesc]);

        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);

        $this->assertEquals($fp1, $fp2);
    }

    /** @test */
    public function test_amount_precision_normalization(): void
    {
        $tx1 = $this->sampleTransaction(['amount' => '1000']);
        $tx2 = $this->sampleTransaction(['amount' => '1000.00']);
        $tx3 = $this->sampleTransaction(['amount' => '1000.0']);

        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);
        $fp3 = $this->fingerprinter->generate($tx3);

        $this->assertEquals($fp1, $fp2);
        $this->assertEquals($fp2, $fp3);
    }

    /** @test */
    public function test_negative_amounts_are_preserved(): void
    {
        $tx1 = $this->sampleTransaction(['amount' => '500.00']);
        $tx2 = $this->sampleTransaction(['amount' => '-500.00']);

        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);

        $this->assertNotEquals($fp1, $fp2);
    }

    // ─── normalizeAmount() tests ──────────────────────────────────────

    /** @test */
    public function test_normalize_amount_integer(): void
    {
        $this->assertEquals('1000.00', $this->fingerprinter->normalizeAmount(1000));
    }

    /** @test */
    public function test_normalize_amount_float(): void
    {
        $this->assertEquals('1000.50', $this->fingerprinter->normalizeAmount(1000.5));
    }

    /** @test */
    public function test_normalize_amount_string(): void
    {
        $this->assertEquals('1000.00', $this->fingerprinter->normalizeAmount('1000'));
    }

    /** @test */
    public function test_normalize_amount_negative(): void
    {
        $this->assertEquals('-500.00', $this->fingerprinter->normalizeAmount('-500'));
    }

    /** @test */
    public function test_normalize_amount_zero(): void
    {
        $this->assertEquals('0.00', $this->fingerprinter->normalizeAmount('0'));
        $this->assertEquals('0.00', $this->fingerprinter->normalizeAmount(0));
    }

    /** @test */
    public function test_normalize_amount_empty_string(): void
    {
        $this->assertEquals('0.00', $this->fingerprinter->normalizeAmount(''));
    }

    // ─── normalizeText() tests ────────────────────────────────────────

    /** @test */
    public function test_normalize_text_lowercase(): void
    {
        $this->assertEquals('helloworld', $this->fingerprinter->normalizeText('Hello World'));
    }

    /** @test */
    public function test_normalize_text_removes_special_chars(): void
    {
        $this->assertEquals('ref12345', $this->fingerprinter->normalizeText('REF-12345'));
    }

    /** @test */
    public function test_normalize_text_preserves_cyrillic(): void
    {
        $result = $this->fingerprinter->normalizeText('Плаќање 123');
        $this->assertEquals('плаќање123', $result);
    }

    /** @test */
    public function test_normalize_text_removes_whitespace(): void
    {
        $this->assertEquals('abc', $this->fingerprinter->normalizeText('  a  b  c  '));
    }

    /** @test */
    public function test_normalize_text_empty_string(): void
    {
        $this->assertEquals('', $this->fingerprinter->normalizeText(''));
    }

    /** @test */
    public function test_normalize_text_only_special_chars(): void
    {
        $this->assertEquals('', $this->fingerprinter->normalizeText('---///...'));
    }

    // ─── normalizeDate() tests ────────────────────────────────────────

    /** @test */
    public function test_normalize_date_string(): void
    {
        $this->assertEquals('2025-11-01', $this->fingerprinter->normalizeDate('2025-11-01'));
    }

    /** @test */
    public function test_normalize_date_datetime_object(): void
    {
        $date = new \DateTime('2025-11-01 14:30:00');
        $this->assertEquals('2025-11-01', $this->fingerprinter->normalizeDate($date));
    }

    /** @test */
    public function test_normalize_date_empty(): void
    {
        $this->assertEquals('', $this->fingerprinter->normalizeDate(''));
    }

    /** @test */
    public function test_normalize_date_different_format(): void
    {
        // PHP DateTime interprets m/d/Y format: 01/11/2025 = Jan 11
        $this->assertEquals('2025-01-11', $this->fingerprinter->normalizeDate('01/11/2025'));
    }

    // ─── Edge cases ───────────────────────────────────────────────────

    /** @test */
    public function test_handles_missing_optional_fields(): void
    {
        $tx = [
            'company_id' => 1,
            'bank_account_id' => 1,
            'transaction_date' => '2025-11-01',
            'amount' => '1000.00',
        ];

        $fingerprint = $this->fingerprinter->generate($tx);

        $this->assertIsString($fingerprint);
        $this->assertEquals(64, strlen($fingerprint));
    }

    /** @test */
    public function test_handles_completely_empty_transaction(): void
    {
        $fingerprint = $this->fingerprinter->generate([]);

        $this->assertIsString($fingerprint);
        $this->assertEquals(64, strlen($fingerprint));
    }

    /** @test */
    public function test_counterparty_account_uses_creditor_then_debtor(): void
    {
        // With only creditor_iban
        $tx1 = $this->sampleTransaction([
            'creditor_iban' => 'MK07250000000000001',
            'debtor_iban' => null,
        ]);

        // With only debtor_iban (no creditor fields)
        $tx2 = $this->sampleTransaction([
            'creditor_account' => null,
            'creditor_iban' => null,
            'debtor_account' => null,
            'debtor_iban' => 'MK07250000000000001',
        ]);

        $fp1 = $this->fingerprinter->generate($tx1);
        $fp2 = $this->fingerprinter->generate($tx2);

        // Both should use the same IBAN value, so fingerprints should match
        $this->assertEquals($fp1, $fp2);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    /**
     * Create a sample transaction array with optional overrides.
     */
    protected function sampleTransaction(array $overrides = []): array
    {
        return array_merge([
            'company_id' => 1,
            'bank_account_id' => 1,
            'transaction_date' => '2025-11-01',
            'amount' => '1500.00',
            'currency' => 'MKD',
            'transaction_type' => 'credit',
            'transaction_reference' => 'REF-2025-001',
            'description' => 'Payment from Customer ABC',
            'creditor_iban' => 'MK07290000000000001',
        ], $overrides);
    }
}

// CLAUDE-CHECKPOINT

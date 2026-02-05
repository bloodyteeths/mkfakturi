<?php

namespace App\Services\Banking\Parsers;

/**
 * CSV Parser Factory
 *
 * Creates the appropriate parser for a given bank or auto-detects
 * the correct parser based on CSV content.
 */
class CsvParserFactory
{
    /**
     * Registered parsers in priority order
     *
     * @var array<class-string<BankParserInterface>>
     */
    protected static array $parsers = [
        NlbCsvParser::class,
        StopanskaСsvParser::class,
        KomercijalnaCsvParser::class,
        GenericCsvParser::class, // Fallback always last
    ];

    /**
     * Create parser by bank code
     */
    public static function createByBankCode(string $bankCode): BankParserInterface
    {
        $bankCode = strtolower($bankCode);

        foreach (self::$parsers as $parserClass) {
            $parser = new $parserClass();
            if ($parser->getBankCode() === $bankCode) {
                return $parser;
            }
        }

        // Return generic parser as fallback
        return new GenericCsvParser();
    }

    /**
     * Auto-detect parser from CSV content
     */
    public static function detectParser(string $content): BankParserInterface
    {
        foreach (self::$parsers as $parserClass) {
            $parser = new $parserClass();

            // Skip generic parser in detection (it's the fallback)
            if ($parser instanceof GenericCsvParser) {
                continue;
            }

            if ($parser->canParse($content)) {
                self::log('info', 'CSV parser auto-detected', [
                    'parser' => $parser->getBankCode(),
                    'bank_name' => $parser->getBankName(),
                ]);
                return $parser;
            }
        }

        // Fallback to generic parser
        self::log('info', 'Using generic CSV parser (no specific bank detected)');
        return new GenericCsvParser();
    }

    /**
     * Get list of supported banks
     */
    public static function getSupportedBanks(): array
    {
        $banks = [];

        foreach (self::$parsers as $parserClass) {
            $parser = new $parserClass();
            if (!$parser instanceof GenericCsvParser) {
                $banks[] = [
                    'code' => $parser->getBankCode(),
                    'name' => $parser->getBankName(),
                    'delimiter' => $parser->getDelimiter() === "\t" ? 'TAB' : $parser->getDelimiter(),
                    'encoding' => $parser->getEncoding(),
                ];
            }
        }

        return $banks;
    }

    /**
     * Register a custom parser
     */
    public static function registerParser(string $parserClass): void
    {
        if (!in_array($parserClass, self::$parsers)) {
            // Insert before generic parser
            $genericIndex = array_search(GenericCsvParser::class, self::$parsers);
            if ($genericIndex !== false) {
                array_splice(self::$parsers, $genericIndex, 0, [$parserClass]);
            } else {
                self::$parsers[] = $parserClass;
            }
        }
    }

    /**
     * Log a message if Laravel's Log facade is available
     */
    protected static function log(string $level, string $message, array $context = []): void
    {
        if (class_exists('\Illuminate\Support\Facades\Log') && function_exists('app') && app()->bound('log')) {
            \Illuminate\Support\Facades\Log::$level($message, $context);
        }
    }
}

// CLAUDE-CHECKPOINT

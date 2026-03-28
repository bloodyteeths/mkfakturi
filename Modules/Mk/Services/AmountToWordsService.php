<?php

namespace Modules\Mk\Services;

/**
 * Converts numeric amounts (in cents) to Macedonian words.
 *
 * Reusable across PP30 payment slips, payment receipts, invoices, etc.
 * Input is always in CENTS — divide by 100 internally.
 *
 * Example: 1500000 → "петнаесет илјади денари и 00 дени"
 */
class AmountToWordsService
{
    /**
     * Units: 0-9
     */
    private const UNITS = [
        '', 'една', 'две', 'три', 'четири',
        'пет', 'шест', 'седум', 'осум', 'девет',
    ];

    /**
     * Masculine units (used for thousands, millions, etc.)
     */
    private const UNITS_MASCULINE = [
        '', 'еден', 'два', 'три', 'четири',
        'пет', 'шест', 'седум', 'осум', 'девет',
    ];

    /**
     * Teens: 10-19
     */
    private const TEENS = [
        'десет', 'единаесет', 'дванаесет', 'тринаесет', 'четиринаесет',
        'петнаесет', 'шеснаесет', 'седумнаесет', 'осумнаесет', 'деветнаесет',
    ];

    /**
     * Tens: 20-90
     */
    private const TENS = [
        '', '', 'дваесет', 'триесет', 'четириесет',
        'педесет', 'шеесет', 'седумдесет', 'осумдесет', 'деведесет',
    ];

    /**
     * Hundreds: 100-900
     */
    private const HUNDREDS = [
        '', 'сто', 'двесте', 'триста', 'четиристотини',
        'петстотини', 'шестстотини', 'седумстотини', 'осумстотини', 'деветстотини',
    ];

    /**
     * Convert amount in cents to Macedonian words with currency suffix.
     *
     * @param  int     $amountCents  Amount in cents (e.g. 1500000 = 15,000.00 MKD)
     * @param  string  $currencyCode Currency code (default MKD)
     * @return string  e.g. "петнаесет илјади денари и 00 дени"
     */
    public function convert(int $amountCents, string $currencyCode = 'MKD'): string
    {
        $whole = intdiv(abs($amountCents), 100);
        $fraction = abs($amountCents) % 100;

        if ($whole === 0) {
            return $this->formatWithCurrency(
                'нула',
                $fraction,
                $currencyCode
            );
        }

        $words = $this->numberToWords($whole);

        return $this->formatWithCurrency($words, $fraction, $currencyCode);
    }

    /**
     * Format the final string with currency suffix.
     */
    protected function formatWithCurrency(string $words, int $fraction, string $currencyCode): string
    {
        $formattedFraction = str_pad((string) $fraction, 2, '0', STR_PAD_LEFT);

        if (strtoupper($currencyCode) === 'MKD') {
            $whole = $words;
            $suffix = ($whole === 'една') ? 'денар' : 'денари';

            return "{$whole} {$suffix} и {$formattedFraction} дени";
        }

        if (strtoupper($currencyCode) === 'EUR') {
            $suffix = ($words === 'едно') ? 'евро' : 'евра';

            return "{$words} {$suffix} и {$formattedFraction} центи";
        }

        // Generic: just number in words
        return "{$words} и {$formattedFraction}/100";
    }

    /**
     * Convert integer to Macedonian words.
     *
     * @param  int    $number  Positive integer
     * @param  bool   $masculine  Use masculine forms (for standalone numbers, thousands, millions)
     * @return string
     */
    public function numberToWords(int $number, bool $masculine = false): string
    {
        if ($number === 0) {
            return 'нула';
        }

        $units = $masculine ? self::UNITS_MASCULINE : self::UNITS;
        $parts = [];

        // Millions
        if ($number >= 1000000) {
            $millions = intdiv($number, 1000000);
            if ($millions === 1) {
                $parts[] = 'еден милион';
            } else {
                $parts[] = $this->numberToWords($millions, true) . ' милиони';
            }
            $number %= 1000000;
        }

        // Thousands
        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            if ($thousands === 1) {
                $parts[] = 'илјада';
            } elseif ($thousands < 10) {
                $parts[] = self::UNITS_MASCULINE[$thousands] . ' илјади';
            } else {
                $parts[] = $this->numberToWords($thousands, true) . ' илјади';
            }
            $number %= 1000;
        }

        // Hundreds
        if ($number >= 100) {
            $h = intdiv($number, 100);
            $parts[] = self::HUNDREDS[$h];
            $number %= 100;
        }

        // Tens and units
        if ($number >= 10 && $number <= 19) {
            $parts[] = self::TEENS[$number - 10];
        } elseif ($number >= 20) {
            $t = intdiv($number, 10);
            $u = $number % 10;
            if ($u > 0) {
                $parts[] = self::TENS[$t] . ' и ' . $units[$u];
            } else {
                $parts[] = self::TENS[$t];
            }
        } elseif ($number > 0) {
            $parts[] = $units[$number];
        }

        return implode(' ', $parts);
    }
}

// CLAUDE-CHECKPOINT

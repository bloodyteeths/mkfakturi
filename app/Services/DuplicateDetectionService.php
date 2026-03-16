<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Reusable duplicate detection service with fuzzy name matching.
 * Supports Cyrillic↔Latin transliteration for Macedonian locale.
 *
 * Extracted from AiNaturalLanguageService for use across all entity creation flows.
 */
class DuplicateDetectionService
{
    /**
     * Find potential duplicate records by fuzzy name matching.
     *
     * @param  string  $modelClass  Fully qualified model class (e.g. Customer::class)
     * @param  int  $companyId  Company scope
     * @param  string  $name  Name to match against
     * @param  int|null  $excludeId  Exclude this record (for updates)
     * @return Collection  Collection of ['record' => Model, 'score' => int, 'match_reason' => string]
     */
    public function findSimilarByName(string $modelClass, int $companyId, string $name, ?int $excludeId = null): Collection
    {
        if (empty(trim($name))) {
            return collect();
        }

        $query = $modelClass::where('company_id', $companyId);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        // Load all columns to avoid $appends accessor crashes on missing columns
        $records = $query->get();

        return $this->findMatches($name, $records, 'name');
    }

    /**
     * Find all fuzzy matches for a needle in a collection.
     *
     * @return Collection  of ['record' => object, 'score' => int, 'match_reason' => string]
     */
    public function findMatches(string $needle, Collection $haystack, string $field): Collection
    {
        if ($haystack->isEmpty() || empty(trim($needle))) {
            return collect();
        }

        $normalizedNeedle = $this->normalizeName($needle);
        $latinNeedleCached = null;
        $matches = collect();

        foreach ($haystack as $record) {
            $dbName = $record->{$field} ?? '';
            if (empty($dbName)) {
                continue;
            }

            $normalizedDb = $this->normalizeName($dbName);

            // 1. Exact normalized match → score 100
            if ($normalizedNeedle === $normalizedDb) {
                $matches->push([
                    'record' => $record,
                    'score' => 100,
                    'match_reason' => 'exact_name',
                ]);

                continue;
            }

            // 2. Containment match → score 90
            if (mb_strlen($normalizedNeedle) >= 2 && mb_strlen($normalizedDb) >= 2) {
                if (str_contains($normalizedDb, $normalizedNeedle) || str_contains($normalizedNeedle, $normalizedDb)) {
                    $matches->push([
                        'record' => $record,
                        'score' => 90,
                        'match_reason' => 'similar_name',
                    ]);

                    continue;
                }
            }

            // 3. Transliterated match (Cyrillic↔Latin)
            if ($latinNeedleCached === null) {
                $latinNeedleCached = $this->cyrillicToLatin($normalizedNeedle);
            }
            $latinDb = $this->cyrillicToLatin($normalizedDb);

            if ($latinNeedleCached === $latinDb) {
                $matches->push([
                    'record' => $record,
                    'score' => 95,
                    'match_reason' => 'transliterated_name',
                ]);

                continue;
            }

            if (mb_strlen($latinNeedleCached) >= 2 && mb_strlen($latinDb) >= 2) {
                if (str_contains($latinDb, $latinNeedleCached) || str_contains($latinNeedleCached, $latinDb)) {
                    $matches->push([
                        'record' => $record,
                        'score' => 85,
                        'match_reason' => 'similar_name',
                    ]);

                    continue;
                }
            }

            // 4. Levenshtein distance (max 255 chars)
            if (mb_strlen($latinNeedleCached) <= 255 && mb_strlen($latinDb) <= 255) {
                $maxLen = max(mb_strlen($latinNeedleCached), mb_strlen($latinDb));
                if ($maxLen > 0) {
                    $distance = levenshtein($latinNeedleCached, $latinDb);
                    $similarity = 1 - ($distance / $maxLen);

                    if ($similarity > 0.7) {
                        $matches->push([
                            'record' => $record,
                            'score' => (int) ($similarity * 80),
                            'match_reason' => 'similar_name',
                        ]);
                    }
                }
            }
        }

        return $matches->sortByDesc('score')->values();
    }

    /**
     * Find the single best match (used by AI auto-creation).
     *
     * @return object|null  The best matching record, or null
     */
    public function bestMatch(string $needle, Collection $haystack, string $field): ?object
    {
        $matches = $this->findMatches($needle, $haystack, $field);

        if ($matches->isEmpty()) {
            return null;
        }

        $best = $matches->first();

        return $best['score'] >= 56 ? $best['record'] : null;
    }

    /**
     * Normalize a name for comparison.
     * Strips: company suffixes, punctuation, extra whitespace. Lowercases.
     */
    public function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));

        // Strip common MK/regional company suffixes
        $suffixes = [
            'дооел', 'доо', 'dooel', 'doo', 'ltd', 'llc', 'gmbh',
            'ад', 'а.д.', 'ad', 'j.p.', 'јп', 'jp',
            'inc', 'corp', 'co', 'kg', 'og',
        ];
        foreach ($suffixes as $suffix) {
            $name = preg_replace('/[\s\.\-,]*'.preg_quote($suffix, '/').'[\s\.\-,]*$/u', '', $name);
        }

        // Strip punctuation and collapse whitespace
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);
        $name = preg_replace('/\s+/u', ' ', $name);

        return trim($name);
    }

    /**
     * Transliterate Macedonian Cyrillic to Latin for cross-script matching.
     */
    public function cyrillicToLatin(string $text): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'ѓ' => 'gj', 'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'ѕ' => 'dz',
            'и' => 'i', 'ј' => 'j', 'к' => 'k', 'л' => 'l', 'љ' => 'lj',
            'м' => 'm', 'н' => 'n', 'њ' => 'nj', 'о' => 'o', 'п' => 'p',
            'р' => 'r', 'с' => 's', 'т' => 't', 'ќ' => 'kj', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'џ' => 'dj',
            'ш' => 'sh',
            'щ' => 'sht', 'ъ' => '', 'ь' => '', 'ю' => 'yu', 'я' => 'ya',
            'э' => 'e', 'ы' => 'i', 'і' => 'i', 'ї' => 'yi', 'є' => 'ye',
        ];

        return strtr($text, $map);
    }
}

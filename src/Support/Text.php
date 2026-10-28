<?php

declare(strict_types=1);

namespace App\Support;

final class Text
{
    private const ACCENTS = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'ç' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
        'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'œ' => 'oe', 'æ' => 'ae',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'ñ' => 'n',
    ];

    /** Lower-case, accent-free, alphanumeric words separated by single spaces. */
    public static function normalize(string $text): string
    {
        $text = strtr(mb_strtolower($text), self::ACCENTS);

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $text));
    }

    /**
     * Search tokens; a trailing plural "s"/"x" is dropped so "pommes" finds "pomme".
     *
     * @return list<string>
     */
    public static function searchTokens(string $text): array
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return [];
        }
        $tokens = array_map(
            static fn (string $t): string => strlen($t) > 3 ? rtrim($t, 'sx') : $t,
            explode(' ', $normalized),
        );

        return array_values(array_unique($tokens));
    }
}

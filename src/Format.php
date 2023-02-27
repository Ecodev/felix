<?php

declare(strict_types=1);

namespace Ecodev\Felix;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Normalizer;

abstract class Format
{
    private const PUNCTUATIONS = [
        '.', '।', '։', '。', '۔', '⳹', '܁', '።', '᙮', '᠃', '⳾', '꓿', '꘎', '꛳', '࠽', '᭟', ',', '،', '、', '՝', '߸', '፣',
        '᠈', '꓾', '꘍', '꛵', '᭞', '⁇', '⁉', '⁈', '‽', '❗', '‼', '⸘', '?', ';', '¿', '؟', '՞', '܆', '፧', '⳺', '⳻', '꘏',
        '꛷', '𑅃', '꫱', '!', '¡', '߹', '᥄', '·', '𐎟', '𐏐', '𒑰', '፡', ' ', '𐤟', '࠰', '—', '–', '‒', '‐', '⁃', '﹣', '－',
        '֊', '᠆', ';', '·', '؛', '፤', '꛶', '․', ':', '፥', '꛴', '᭝', '…', '︙', 'ຯ', '«', '‹', '»', '›', '„', '‚', '“',
        '‟', '‘', '‛', '”', '’', '"', "'", '(', ')',
    ];

    /**
     * Truncate a string and append '…' at the end.
     *
     * @param string $ellipsis the string to indicate truncation happened
     *
     * @return string truncated string
     */
    public static function truncate(string $string, int $maxLength, string $ellipsis = '…'): string
    {
        if (mb_strlen($string) > $maxLength) {
            $string = mb_substr($string, 0, $maxLength - mb_strlen($ellipsis));
            $string .= $ellipsis;
        }

        return $string;
    }

    /**
     * Shortcut to format money.
     */
    public static function money(Money $money): string
    {
        $currencies = new ISOCurrencies();
        $moneyFormatter = new DecimalMoneyFormatter($currencies);

        return $moneyFormatter->format($money);
    }

    /**
     * Parse the term to extract a list of words and quoted terms.
     *
     * @return string[]
     */
    public static function splitSearchTerms(?string $term): array
    {
        if (!$term) {
            return [];
        }

        /** @var string $term */
        $term = Normalizer::normalize($term);

        // Drop empty quote
        $term = str_replace('""', '', $term);

        // Extract exact terms that are quoted
        preg_match_all('~"([^"]*)"~', $term, $m);
        $exactTerms = $m[1];
        $termWithoutExact = str_replace($m[0], ' ', $term);
        $termWithoutExactWithoutPunctuations = str_replace(self::PUNCTUATIONS, ' ', $termWithoutExact);

        // Split words by any whitespace
        $words = preg_split('/[[:space:]]+/', $termWithoutExactWithoutPunctuations, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Combine both list
        if ($exactTerms) {
            array_push($words, ...$exactTerms);
        }

        // Drop duplicates
        $words = array_unique($words);

        return $words;
    }
}

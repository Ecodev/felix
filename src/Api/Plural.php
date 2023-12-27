<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api;

final class Plural
{
    /**
     * Returns the plural form of the given name.
     *
     * This is **not** necessarily valid english grammar. Its only purpose is for internal usage, not for humans.
     *
     * This **MUST** be kept in sync with Natural's `makePlural()`.
     *
     * This is a bit performance-sensitive, so we should keep it fast and only cover cases that we actually need.
     */
    public static function make(string $name): string
    {
        // Words ending in a y preceded by a vowel form their plurals by adding -s:
        if (preg_match('/[aeiou]y$/', $name)) {
            return $name . 's';
        }

        $plural = $name . 's';
        $plural = preg_replace('/ys$/', 'ies', $plural);
        if ($plural === null) {
            throw new Exception('Error while making plural');
        }

        $plural = preg_replace('/ss$/', 'ses', $plural);
        if ($plural === null) {
            throw new Exception('Error while making plural');
        }

        return $plural;
    }
}

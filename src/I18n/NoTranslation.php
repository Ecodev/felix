<?php

declare(strict_types=1);

namespace Ecodev\Felix\I18n;

/**
 * Translator that does not translate at all.
 *
 * It is the default configuration of Felix. This should be used in application that are not translated.
 */
class NoTranslation implements Translator
{
    public function translate(string $message): string
    {
        return $message;
    }
}

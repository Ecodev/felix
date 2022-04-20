<?php

declare(strict_types=1);

namespace Ecodev\Felix\I18n;

/**
 * Used to configure Felix internal translator.
 *
 * By default, Felix is configured with `NoTranslation`. But applications that need
 * to be translated need to configure it with something else. Most likely a
 * `\Laminas\I18n\Translator\TranslatorInterface`.
 *
 * You should use the global `_tr()` to translate messages in application in order
 * to benefit from token replacements, and message extraction via Poedit.
 */
interface Translator
{
    /**
     * Returns the translated version of the given message into current language.
     *
     * @internal instead of this, use the global `_tr()`
     */
    public function translate(string $message): string;
}

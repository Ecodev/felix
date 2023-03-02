<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Ecodev\Felix\Debug;
use Ecodev\Felix\I18n\Translator;
use GraphQL\Doctrine\Types;
use Laminas\Log\LoggerInterface;

/**
 * Returns the type registry.
 */
function _types(): Types
{
    global $container;

    return $container->get(Types::class);
}

/**
 * Returns the Entity Manager.
 */
function _em(): EntityManager
{
    global $container;

    return $container->get(EntityManager::class);
}

/**
 * Returns logger.
 */
function _log(): LoggerInterface
{
    global $container;

    return $container->get(LoggerInterface::class);
}

/**
 * Export variables omitting array keys that are strictly numeric.
 *
 * By default, it will output result
 *
 * @return string string representation of variable
 */
function ve(mixed $data, bool $return = false): string
{
    return Debug::export($data, $return);
}

/**
 * Dump all arguments.
 */
function v(): void
{
    var_dump(func_get_args());
}

/**
 * Dump all arguments and die.
 */
function w(): never
{
    $isHtml = (PHP_SAPI !== 'cli');
    echo "\n_________________________________________________________________________________________________________________________" . ($isHtml ? '</br>' : '') . "\n";
    var_dump(func_get_args());
    echo "\n" . ($isHtml ? '</br>' : '') . '_________________________________________________________________________________________________________________________' . ($isHtml ? '<pre>' : '') . "\n";
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    echo '' . ($isHtml ? '</pre>' : '') . '_________________________________________________________________________________________________________________________' . ($isHtml ? '</br>' : '') . "\n";
    exit("script aborted on purpose.\n");
}

/**
 * Translate given message in current language.
 *
 * If replacements are given, they will be replaced after translation:
 *
 * ```php
 * _tr('Hello %my-name%', ['my-name' => 'John']); // Bonjour John
 * ```
 *
 * @param array<string, null|float|int|string> $replacements
 */
function _tr(string $message, array $replacements = []): string
{
    global $container;

    $translator = $container->get(Translator::class);
    $translation = $translator->translate($message);
    if (!$replacements) {
        return $translation;
    }

    $finalReplacements = [];
    foreach ($replacements as $key => $value) {
        $finalReplacements['%' . $key . '%'] = $value;
    }

    return strtr($translation, $finalReplacements);
}

<?php

declare(strict_types=1);

namespace Ecodev\Felix\I18n;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class NoTranslationFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Translator
    {
        return new NoTranslation();
    }
}

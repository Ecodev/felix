<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class EventCompleterFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): EventCompleter
    {
        $config = $container->get('config');
        $baseUrl = 'https://' . $config['hostname'];

        return new EventCompleter($baseUrl);
    }
}

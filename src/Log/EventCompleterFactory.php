<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class EventCompleterFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): EventCompleter
    {
        /** @var array $config */
        $config = $container->get('config');
        $baseUrl = 'https://' . $config['hostname'];

        return new EventCompleter($baseUrl);
    }
}

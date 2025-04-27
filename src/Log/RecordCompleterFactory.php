<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class RecordCompleterFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): RecordCompleter
    {
        /** @var array $config */
        $config = $container->get('config');
        $baseUrl = 'https://' . $config['hostname'];

        return new RecordCompleter($baseUrl);
    }
}

<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Logging;

use Ecodev\Felix\Log\Handler\DbHandler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MiddlewareFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Middleware
    {
        /** @var array $config */
        $config = $container->get('config');
        $logSql = $config['logSql'];

        $dbHandler = $container->get(DbHandler::class);

        return new Middleware($dbHandler, $logSql);
    }
}

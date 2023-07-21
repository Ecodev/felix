<?php

declare(strict_types=1);

namespace Ecodev\Felix\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SignedQueryMiddlewareFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SignedQueryMiddleware
    {
        /** @var array $config */
        $config = $container->get('config');
        $signedQueries = $config['signedQueries'];

        return new SignedQueryMiddleware($signedQueries['keys'], $signedQueries['required']);
    }
}

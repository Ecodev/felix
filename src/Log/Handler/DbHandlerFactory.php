<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log\Handler;

use Ecodev\Felix\Log\RecordCompleter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class DbHandlerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): DbHandler
    {
        /** @var RecordCompleter $recordCompleter */
        $recordCompleter = $container->get(RecordCompleter::class);

        $dbHandler = new DbHandler(fn () => _em()->getRepository(\Application\Model\Log::class));
        $dbHandler->pushProcessor($recordCompleter);

        return $dbHandler;
    }
}

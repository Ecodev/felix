<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log;

use Ecodev\Felix\Log\Handler\DbHandler;
use Ecodev\Felix\Log\Handler\MailerHandler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LoggerFactory implements FactoryInterface
{
    private ?Logger $logger = null;

    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): LoggerInterface
    {
        if (!$this->logger) {
            $this->logger = new Logger('app');

            // Log to file
            $this->logger->pushHandler(new StreamHandler('logs/all.log'));

            // Log to DB
            /** @var DbHandler $dbHandler */
            $dbHandler = $container->get(DbHandler::class);
            $this->logger->pushHandler($dbHandler);

            // Maybe log to emails
            /** @var null|MailerHandler $mailHandler */
            $mailHandler = $container->get(MailerHandler::class);
            if ($mailHandler) {
                $this->logger->pushHandler($mailHandler);
            }

            // Register to log all kinds of PHP errors
            ErrorHandler::register($this->logger);
        }

        return $this->logger;
    }
}

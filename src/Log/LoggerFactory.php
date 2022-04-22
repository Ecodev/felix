<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log;

use Ecodev\Felix\Log\Writer\Db;
use Ecodev\Felix\Log\Writer\Mail;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class LoggerFactory implements FactoryInterface
{
    private ?Logger $logger = null;

    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Logger
    {
        if (!$this->logger) {
            $this->logger = new Logger();

            // Log to file
            $fileWriter = new Stream('logs/all.log');
            $this->logger->addWriter($fileWriter);

            // Log to DB
            /** @var Db $dbWriter */
            $dbWriter = $container->get(Db::class);
            $dbWriter->addFilter(Logger::INFO);
            $this->logger->addWriter($dbWriter);

            // Maybe log to emails
            /** @var null|Mail $mailWriter */
            $mailWriter = $container->get(Mail::class);
            if ($mailWriter) {
                $this->logger->addWriter($mailWriter);
            }

            // Register to log all kind of PHP errors
            Logger::registerErrorHandler($this->logger, true);
            Logger::registerFatalErrorShutdownFunction($this->logger);
        }

        return $this->logger;
    }
}

<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log\Handler;

use Closure;
use Ecodev\Felix\Repository\LogRepository;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Will log INFO or more to database, but only if a DB connection has already been connected,
 * otherwise all log records are entirely ignored.
 *
 * `\Application\Model\Log` **MUST** exist and implement `\Ecodev\Felix\Repository\LogRepository`.
 */
class DbHandler extends AbstractProcessingHandler
{
    private LogRepository $logRepository;

    private bool $enabled = false;

    public function __construct(
        /**
         * @var Closure(): LogRepository
         */
        private readonly Closure $logRepositoryGetter,
    ) {
        parent::__construct(Level::Info);
    }

    public function isHandling(LogRecord $record): bool
    {
        return $this->enabled && parent::isHandling($record);
    }

    /**
     * Write a message to the DB.
     */
    protected function write(LogRecord $record): void
    {
        if (!isset($this->logRepository)) {
            $this->logRepository = $this->logRepositoryGetter->__invoke();
        }

        $this->logRepository->log($record);
    }

    public function enable(): void
    {
        $this->enabled = true;
    }
}

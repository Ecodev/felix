<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log\Writer;

use Ecodev\Felix\Log\EventCompleter;
use Ecodev\Felix\Repository\LogRepository;
use Laminas\Log\Writer\AbstractWriter;

class Db extends AbstractWriter
{
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @var EventCompleter
     */
    private $eventCompleter;

    public function __construct(LogRepository $logRepository, EventCompleter $extrasCompleter, $options = null)
    {
        parent::__construct($options);
        $this->logRepository = $logRepository;
        $this->eventCompleter = $extrasCompleter;
    }

    /**
     * Write a message to the log.
     *
     * @param array $event log data event
     */
    final protected function doWrite(array $event): void
    {
        $completedEvent = $this->eventCompleter->process($event);
        $this->logRepository->log($completedEvent);
    }
}

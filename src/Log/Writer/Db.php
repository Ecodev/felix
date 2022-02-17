<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log\Writer;

use Ecodev\Felix\Log\EventCompleter;
use Ecodev\Felix\Repository\LogRepository;
use Laminas\Log\Writer\AbstractWriter;

class Db extends AbstractWriter
{
    public function __construct(private readonly LogRepository $logRepository, private readonly EventCompleter $eventCompleter, $options = null)
    {
        parent::__construct($options);
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

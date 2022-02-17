<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log\Writer;

use Ecodev\Felix\Log\EventCompleter;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;

class Mail extends \Laminas\Log\Writer\Mail
{
    public function __construct(Message $mail, TransportInterface $transport, private readonly EventCompleter $eventCompleter)
    {
        parent::__construct($mail, $transport);
    }

    /**
     * Write a message to the log.
     *
     * @param array $event log data event
     */
    final protected function doWrite(array $event): void
    {
        $completedEvent = $this->eventCompleter->process($event);
        parent::doWrite($completedEvent);
    }
}

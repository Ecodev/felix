<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log;

use PHPUnit\Framework\Assert;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class InMemoryTransport implements TransportInterface
{
    public Email $lastMessage;

    public function __toString(): string
    {
        return '';
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        Assert::assertInstanceOf(Email::class, $message);
        $this->lastMessage = $message;

        return null;
    }
}

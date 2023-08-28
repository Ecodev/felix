<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log\Writer;

use DateTimeImmutable;
use Ecodev\Felix\Api\Exception;
use Ecodev\Felix\Api\ExceptionWithoutMailLogging;
use Ecodev\Felix\Log\EventCompleter;
use Ecodev\Felix\Log\EventCompleterFactory;
use Ecodev\Felix\Log\Formatter\Extras;
use Ecodev\Felix\Log\Writer\MailFactory;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Mail;
use Laminas\Log\Writer\WriterInterface;
use Laminas\Mail\Transport\InMemory;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Throwable;

class MailFactoryTest extends TestCase
{
    public function testReturnNullIfNoEmailsConfiguredAtAll(): void
    {
        $container = new ServiceManager([
            'services' => [
                'config' => [],
            ],
        ]);

        $factory = new MailFactory();
        $actual = $factory($container, '');
        self::assertNull($actual);
    }

    public function testReturnNullIfEmptyEmails(): void
    {
        $container = new ServiceManager([
            'services' => [
                'config' => [
                    'log' => [
                        'emails' => [],
                    ],
                ],
            ],
        ]);

        $factory = new MailFactory();
        $actual = $factory($container, '');
        self::assertNull($actual);
    }

    private function createWriter(): array
    {
        $transport = new InMemory();
        $container = new ServiceManager([
            'services' => [
                'config' => [
                    'hostname' => 'example.com',
                    'email' => [
                        'from' => ['noreply@example.com'],
                    ],
                    'log' => [
                        'emails' => ['developers@example.com'],
                    ],
                ],
            ],
            'invokables' => [
                Extras::class => Extras::class,
            ],
            'factories' => [
                EventCompleter::class => EventCompleterFactory::class,
                TransportInterface::class => fn () => $transport,
            ],
        ]);

        $factory = new MailFactory();
        $writer = $factory($container, '');

        self::assertInstanceOf(Mail::class, $writer);
        self::assertNull($transport->getLastMessage(), 'no message sent yet');

        return [$writer, $transport];
    }

    public function testLogError(): void
    {
        [$writer, $transport] = $this->createWriter();

        $this->writeOne($writer, null);
        self::assertTrue($transport->getLastMessage()->getTo()->has('developers@example.com'), 'must be sent to developers');
        self::assertStringContainsString('REFERER', $transport->getLastMessage()->getBody(), 'must contains the extra field REFERER');
    }

    public function testLogException(): void
    {
        [$writer, $transport] = $this->createWriter();
        $this->writeOne($writer, new Exception('some exception'));
        self::assertStringContainsString('REFERER', $transport->getLastMessage()->getBody(), 'must contains the extra field REFERER');
    }

    public function testDoesNotLogIgnoredException(): void
    {
        [$writer, $transport] = $this->createWriter();

        $this->writeOne($writer, new ExceptionWithoutMailLogging('some non logged exception'));
        self::assertNull($transport->getLastMessage(), 'still no message because the exception was marked as ignored');
    }

    private function writeOne(WriterInterface $writer, ?Throwable $exception): void
    {
        $writer->write([
            'timestamp' => new DateTimeImmutable(),
            'priority' => Logger::ERR,
            'priorityName' => 'some priority name',
            'message' => 'some message',
            'extra' => ['exception' => $exception],
        ]);

        $writer->shutdown();
    }
}

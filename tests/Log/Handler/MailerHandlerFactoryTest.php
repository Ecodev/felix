<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log\Handler;

use DateTimeImmutable;
use Ecodev\Felix\Api\Exception;
use Ecodev\Felix\Api\ExceptionWithoutMailLogging;
use Ecodev\Felix\Log\Handler\MailerHandler;
use Ecodev\Felix\Log\Handler\MailerHandlerFactory;
use Ecodev\Felix\Log\RecordCompleter;
use Ecodev\Felix\Log\RecordCompleterFactory;
use EcodevTests\Felix\Log\InMemoryTransport;
use Laminas\ServiceManager\ServiceManager;
use Monolog\Handler\Handler;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Throwable;

class MailerHandlerFactoryTest extends TestCase
{
    public function testReturnNullIfNoEmailsConfiguredAtAll(): void
    {
        $container = new ServiceManager([
            'services' => [
                'config' => [],
            ],
        ]);

        $factory = new MailerHandlerFactory();
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

        $factory = new MailerHandlerFactory();
        $actual = $factory($container, '');
        self::assertNull($actual);
    }

    /**
     * @return array{0: Handler, 1: InMemoryTransport}
     */
    private function createHandler(): array
    {
        $transport = new InMemoryTransport();

        $container = new ServiceManager([
            'services' => [
                'config' => [
                    'hostname' => 'example.com',
                    'email' => [
                        'from' => 'noreply@example.com',
                    ],
                    'log' => [
                        'emails' => ['developers@example.com'],
                    ],
                ],
            ],
            'factories' => [
                RecordCompleter::class => RecordCompleterFactory::class,
                TransportInterface::class => fn () => $transport,
            ],
        ]);

        $factory = new MailerHandlerFactory();
        $handler = $factory($container, '');

        self::assertInstanceOf(MailerHandler::class, $handler);
        self::assertFalse(isset($transport->lastMessage), 'no message sent yet');

        return [$handler, $transport];
    }

    public function testLogError(): void
    {
        [$handler, $transport] = $this->createHandler();

        $this->handleOne($handler, null);
        $recipients = $transport->lastMessage->getTo();
        self::assertCount(1, $recipients, 'must be sent to 1 recipient');
        self::assertSame('developers@example.com', $recipients[0]->getAddress(), 'must be sent to developers');
        self::assertStringContainsString('referer', $transport->lastMessage->getHtmlBody(), 'must contains the extra field REFERER');
    }

    public function testLogException(): void
    {
        [$handler, $transport] = $this->createHandler();
        $this->handleOne($handler, new Exception('some exception'));
        self::assertStringContainsString('referer', $transport->lastMessage->getHtmlBody(), 'must contains the extra field REFERER');
    }

    public function testDoesNotLogIgnoredException(): void
    {
        [$handler, $transport] = $this->createHandler();

        $this->handleOne($handler, new ExceptionWithoutMailLogging('some non logged exception'));
        self::assertFalse(isset($transport->lastMessage), 'still no message because the exception was marked as ignored');
    }

    private function handleOne(Handler $handler, ?Throwable $exception): void
    {
        $handler->handle(
            new LogRecord(
                new DateTimeImmutable(),
                '',
                Level::Error,
                'some message',
                ['exception' => $exception],
            ),
        );

        $handler->close();
    }
}

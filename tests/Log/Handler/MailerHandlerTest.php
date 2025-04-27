<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log\Handler;

use DateTimeImmutable;
use Ecodev\Felix\Api\ExceptionWithoutMailLogging;
use Ecodev\Felix\Log\Handler\MailerHandler;
use Exception;
use GraphQL\Error\Error;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

class MailerHandlerTest extends TestCase
{
    /**
     * @dataProvider providerHandle
     */
    public function testHandle(array $event, bool $expected): void
    {
        $record = new LogRecord(
            new DateTimeImmutable(),
            '',
            Level::Error,
            '',
            $event,
        );

        $mockObject = $this->createMock(TransportInterface::class);
        $mockObject->expects(self::exactly($expected ? 1 : 0))
            ->method('send');

        $handler = new MailerHandler($mockObject, new Email());
        $actual = $handler->isHandling($record);

        self::assertSame($expected, $actual);

        $handler->handle($record);
    }

    public static function providerHandle(): array
    {
        return [
            [[], true],
            [['exception' => null], true],
            [['exception' => []], true],
            [['exception' => new Exception()], true],
            [['exception' => new Exception('', 0, new Exception())], true],
            [['exception' => new Exception('', 0, new ExceptionWithoutMailLogging())], true],
            [['exception' => new ExceptionWithoutMailLogging()], false],
            [['exception' => new Error()], true],
            [['exception' => new Error('', null, null, [], null, new Exception())], true],
            [['exception' => new Error('', null, null, [], null, new ExceptionWithoutMailLogging())], false],
        ];
    }
}

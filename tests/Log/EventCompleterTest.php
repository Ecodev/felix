<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log;

use DateTimeImmutable;
use Ecodev\Felix\Log\RecordCompleter;
use Ecodev\Felix\Model\CurrentUser;
use Ecodev\Felix\Model\User;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class EventCompleterTest extends TestCase
{
    protected function tearDown(): void
    {
        CurrentUser::set(null);
        $_REQUEST = [];
    }

    public function testProcessMinimal(): void
    {
        $completed = new RecordCompleter('https://example.com');
        $completedRecord = $completed->__invoke(
            new LogRecord(
                new DateTimeImmutable(),
                '',
                Level::Error,
                'some message',
            )
        );
        $actual = $completedRecord->extra;

        self::assertNull($actual['creator_id']);
        self::assertSame('<anonymous>', $actual['login']);
        self::assertIsString($actual['url']);
        self::assertIsString($actual['referer']);
        self::assertIsString($actual['request']);
        self::assertSame('script', $actual['ip']);
    }

    public function testProcess(): void
    {
        $user = $this->createMock(User::class);
        $user->expects(self::once())
            ->method('getId')
            ->willReturn(123);

        $user->expects(self::once())
            ->method('getLogin')
            ->willReturn('my login');

        CurrentUser::set($user);
        putenv('REMOTE_ADDR=127.0.0.1');
        $_REQUEST = [
            'password' => 'sensitive',
            'variables' => [
                'other' => [
                    'password' => 'sensitive',
                    'passwordConfirmation' => 'sensitive',
                    'foo' => 123,
                ],
            ],
        ];

        $completed = new RecordCompleter('https://example.com');
        $completedRecord = $completed->__invoke(
            new LogRecord(
                new DateTimeImmutable(),
                '',
                Level::Error,
                'some message',
                [
                    'errno' => 1,
                    'password' => 'sensitive',
                ]
            )
        );
        $actual = $completedRecord->extra;

        self::assertSame([
            'errno' => 1,
            'password' => '***REDACTED***',
        ], $completedRecord->context);
        self::assertSame(123, $actual['creator_id']);
        self::assertSame('my login', $actual['login']);
        self::assertIsString($actual['url']);
        self::assertIsString($actual['referer']);
        self::assertSame([
            'password' => '***REDACTED***',
            'variables' => [
                'other' => [
                    'password' => '***REDACTED***',
                    'passwordConfirmation' => '***REDACTED***',
                    'foo' => 123,
                ],
            ],
        ], json_decode($actual['request'], true));
        self::assertSame('127.0.0.1', $actual['ip']);
    }
}

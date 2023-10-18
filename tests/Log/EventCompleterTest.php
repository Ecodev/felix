<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log;

use Ecodev\Felix\Log\EventCompleter;
use Ecodev\Felix\Model\CurrentUser;
use Ecodev\Felix\Model\User;
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
        $completed = new EventCompleter('https://example.com');
        $actual = $completed->process([]);
        self::assertNull($actual['creator_id']);
        self::assertNull($actual['login']);
        self::assertIsString($actual['url']);
        self::assertIsString($actual['referer']);
        self::assertIsString($actual['request']);
        self::assertSame('script', $actual['ip']);
    }

    public function testProcess(): void
    {
        $user = self::createMock(User::class);
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
                    'npass2' => [123],
                    'foo' => 123,
                ],
            ],
        ];

        $completed = new EventCompleter('https://example.com');
        $actual = $completed->process([
            'message' => '',
            'extra' => [
                'errno' => 1,
            ],
        ]);

        self::assertStringContainsString('Stacktrace:', $actual['message']);
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
                    'npass2' => '***REDACTED***',
                    'foo' => 123,
                ],
            ],
        ], json_decode($actual['request'], true));
        self::assertSame('127.0.0.1', $actual['ip']);
    }
}

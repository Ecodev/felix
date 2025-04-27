<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log;

use Ecodev\Felix\Log\Handler\DbHandler;
use Ecodev\Felix\Log\Handler\MailerHandler;
use Ecodev\Felix\Log\LoggerFactory;
use Laminas\ServiceManager\ServiceManager;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LoggerFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        @mkdir('logs');
    }

    protected function tearDown(): void
    {
        shell_exec('rm -rf logs/');
    }

    public function testWithMailHandler(): void
    {
        $container = new ServiceManager([
            'factories' => [
                DbHandler::class => fn () => $this->createMock(DbHandler::class),
                MailerHandler::class => fn () => $this->createMock(MailerHandler::class),
            ],
        ]);

        $factory = new LoggerFactory();
        $actual = $factory($container, '');
        self::assertInstanceOf(Logger::class, $actual);
        self::assertCount(3, $actual->getHandlers());
    }

    public function testWithoutMailHandler(): void
    {
        $container = new ServiceManager([
            'factories' => [
                DbHandler::class => fn () => $this->createMock(DbHandler::class),
                MailerHandler::class => fn () => null,
            ],
        ]);

        $factory = new LoggerFactory();
        $actual = $factory($container, '');
        self::assertInstanceOf(Logger::class, $actual);
        self::assertCount(2, $actual->getHandlers());
    }
}

<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log;

use Ecodev\Felix\Log\LoggerFactory;
use Ecodev\Felix\Log\Writer\Db;
use Ecodev\Felix\Log\Writer\Mail;
use Laminas\Log\Logger;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class LoggerFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        @mkdir('logs');
    }

    protected function tearDown(): void
    {
        Logger::unregisterErrorHandler();
        Logger::unregisterExceptionHandler();
        shell_exec('rm -rf logs/');
    }

    public function testWithMailWriter(): void
    {
        $container = new ServiceManager([
            'factories' => [
                Db::class => function () {
                    return self::createMock(Db::class);
                },
                Mail::class => function () {
                    return self::createMock(Mail::class);
                },
            ],
        ]);

        $factory = new LoggerFactory();
        $actual = $factory($container, '');
        self::assertInstanceOf(Logger::class, $actual);
        self::assertCount(3, $actual->getWriters());
    }

    public function testWithoutMailWriter(): void
    {
        $container = new ServiceManager([
            'factories' => [
                Db::class => function () {
                    return self::createMock(Db::class);
                },
                Mail::class => function () {
                    return null;
                },
            ],
        ]);

        $factory = new LoggerFactory();
        $actual = $factory($container, '');
        self::assertInstanceOf(Logger::class, $actual);
        self::assertCount(2, $actual->getWriters());
    }
}

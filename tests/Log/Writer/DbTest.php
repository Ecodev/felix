<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log\Writer;

use Ecodev\Felix\Log\EventCompleter;
use Ecodev\Felix\Log\Writer\Db;
use Ecodev\Felix\Repository\LogRepository;
use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{
    public function testWriteCompletedEventInDb(): void
    {
        $event = ['message' => 'original'];
        $completedEvent = ['message' => 'completed'];

        $logRepository = self::createMock(LogRepository::class);
        $logRepository->expects(self::once())
            ->method('log')
            ->with($completedEvent);

        $eventCompleter = self::createMock(EventCompleter::class);
        $eventCompleter->expects(self::once())
            ->method('process')
            ->with($event)
            ->willReturn($completedEvent);

        $writer = new Db(fn () => $logRepository, $eventCompleter);
        $writer->write($event);
    }
}

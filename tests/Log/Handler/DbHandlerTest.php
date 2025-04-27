<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log\Handler;

use DateTimeImmutable;
use Ecodev\Felix\Log\Handler\DbHandler;
use Ecodev\Felix\Repository\LogRepository;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class DbHandlerTest extends TestCase
{
    private LogRecord $record;

    protected function setUp(): void
    {
        $this->record = new LogRecord(
            new DateTimeImmutable(),
            '',
            Level::Info,
            '',
        );
    }

    public function testHandleDoesNothingWithoutEnabling(): void
    {
        $logRepository = $this->createMock(LogRepository::class);
        $logRepository->expects(self::never())
            ->method('log')
            ->with($this->record);

        $handler = new DbHandler(fn () => $logRepository);
        $handler->handle($this->record);
    }

    public function testHandleWriteIfEnabled(): void
    {
        $logRepository = $this->createMock(LogRepository::class);
        $logRepository->expects(self::once())
            ->method('log')
            ->with($this->record);

        $handler = new DbHandler(fn () => $logRepository);
        $handler->enable();
        $handler->handle($this->record);
    }
}

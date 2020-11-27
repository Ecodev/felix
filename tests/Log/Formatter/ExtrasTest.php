<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log\Formatter;

use Ecodev\Felix\Log\Formatter\Extras;
use PHPUnit\Framework\TestCase;

class ExtrasTest extends TestCase
{
    public function testEachValueMustAppearSomeWhereInTheTemplate(): void
    {
        $event = [
            'timestamp' => 'my timestamp',
            'priorityName' => 'my priorityName',
            'priority' => 'my priority',
            'login' => 'my login',
            'url' => 'my url',
            'referer' => 'my referer',
            'ip' => 'my ip',
            'request' => 'my request',
            'message' => 'my message',
        ];

        $formatter = new Extras();
        $actual = $formatter->format($event);

        foreach ($event as $value) {
            self::assertStringContainsString($value, $actual);
        }
    }
}

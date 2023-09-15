<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Log\Filter;

use Ecodev\Felix\Api\ExceptionWithoutMailLogging;
use Ecodev\Felix\Log\Filter\NoMail;
use Exception;
use GraphQL\Error\Error;
use PHPUnit\Framework\TestCase;

class NoMailTest extends TestCase
{
    /**
     * @dataProvider providerFilter
     */
    public function testFilter(array $event, bool $expected): void
    {
        $filter = new NoMail();
        $actual = $filter->filter($event);
        self::assertSame($expected, $actual);
    }

    public static function providerFilter(): array
    {
        return [
            [[], true],
            [['extra' => []], true],
            [['extra' => ['exception' => null]], true],
            [['extra' => ['exception' => []]], true],
            [['extra' => ['exception' => new Exception()]], true],
            [['extra' => ['exception' => new Exception('', 0, new Exception())]], true],
            [['extra' => ['exception' => new Exception('', 0, new ExceptionWithoutMailLogging())]], true],
            [['extra' => ['exception' => new ExceptionWithoutMailLogging()]], false],
            [['extra' => ['exception' => new Error()]], true],
            [['extra' => ['exception' => new Error('', null, null, [], null, new Exception())]], true],
            [['extra' => ['exception' => new Error('', null, null, [], null, new ExceptionWithoutMailLogging())]], false],
        ];
    }
}

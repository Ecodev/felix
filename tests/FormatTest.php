<?php

declare(strict_types=1);

namespace EcodevTests\Felix;

use Ecodev\Felix\Format;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormatTest extends TestCase
{
    #[DataProvider('providerTruncate')]
    public function testTruncate(array $args, string $expected): void
    {
        $actual = Format::truncate(...$args);
        self::assertSame($expected, $actual);
    }

    public static function providerTruncate(): iterable
    {
        return [
            [['abcdef', 100], 'abcdef'],
            [['abcdef', 6], 'abcdef'],
            [['abcdef', 3], 'ab…'],
            [['abcdef', 3, ''], 'abc'],
            [['abcdefghi', 5, 'foo'], 'abfoo'],
        ];
    }

    #[DataProvider('providerSplitSearchTerms')]
    public function testSplitSearchTerms(?string $search, array $expected): void
    {
        self::assertSame($expected, Format::splitSearchTerms($search));
    }

    public static function providerSplitSearchTerms(): iterable
    {
        yield [null, []];
        yield 'empty term' => ['', []];
        yield 'only whitespace is dropped' => ['  ', []];
        yield 'quoted whitespace is kept' => ['"    "', ['    ']];
        yield 'empty quoted term' => ['""', []];
        yield 'mixed empty term' => ['   ""  ""  ', []];
        yield 'mixed quoted and non-quoted' => [' a b "john doe" c d e " f g h i j k l m n o p q r s t u v w x y z "  ', ['a', 'b', 'c', 'd', 'e', 'john doe', ' f g h i j k l m n o p q r s t u v w x y z ']];
        yield 'normal' => ['foo', ['foo']];
        yield 'quoted words are not split' => ['"john doe"', ['john doe']];
        yield 'trimmed split words' => [' foo   bar ', ['foo', 'bar']];
        yield 'ignore ()' => [' foo   (bar) ', ['foo', 'bar']];
        yield 'no duplicates' => ['a a "a" a "a" a', ['a']];
        yield 'combined diacritical marks are normalized' => [
            // This is a special "é" that is combination of letter "e" and the diacritic "◌́". It can be produced by macOS.
            html_entity_decode('e&#769;'),
            ['é'], // This is a totally normal "é"
        ];
        yield 'confusing punctuation marks are ignored, according to https://unicode-table.com/en/sets/punctuation-marks' => [
            'a\'.a।a։a。a۔a⳹a܁a።a᙮a᠃a⳾a꓿a꘎a꛳a࠽a᭟a,a،a、a՝a߸a፣a᠈a꓾a꘍a꛵a᭞a⁇a⁉a⁈a‽a❗a‼a⸘a?a;a¿a؟a՞a܆a፧a⳺a⳻a꘏a꛷a𑅃a꫱a!a¡a߹a᥄a·a𐎟a𐏐a𒑰a፡a a𐤟a࠰a—a–a‒a‐a⁃a﹣a－a֊a᠆a;a·a؛a፤a꛶a․a:a፥a꛴a᭝a…a︙aຯa«a‹a»a›a„a‚a“a‟a‘a‛a”a’a"a',
            ['a'],
        ];
        yield 'confusing punctuation can still be used if quoted' => [
            '"’\'"',
            ['’\''],
        ];
    }

    public function testMoney(): void
    {
        self::assertSame('12.34', Format::money(Money::CHF(1234)));
    }
}

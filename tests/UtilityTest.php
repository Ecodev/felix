<?php

declare(strict_types=1);

namespace EcodevTests\Felix;

use Ecodev\Felix\Model\Model;
use Ecodev\Felix\Utility;
use EcodevTests\Felix\Blog\Model\User;
use EcodevTests\Felix\Traits\TestWithEntityManager;
use GraphQL\Doctrine\Definition\EntityID;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;

final class UtilityTest extends TestCase
{
    use TestWithEntityManager;

    public function testGetShortClassName(): void
    {
        self::assertSame('User', Utility::getShortClassName(new User()));
        self::assertSame('User', Utility::getShortClassName(User::class));
    }

    public function testEntityIdToModel(): void
    {
        $fakeEntity = new stdClass();
        $input = [
            3 => new stdClass(),
            4 => 1,
            'model' => new User(),
            'entity' => new class($fakeEntity) extends EntityID {
                public function __construct(private readonly object $fakeEntity)
                {
                }

                public function getEntity(): object
                {
                    return $this->fakeEntity;
                }
            },
        ];

        $actual = Utility::entityIdToModel($input);

        $expected = $input;
        $expected['entity'] = $fakeEntity;

        self::assertSame($expected, $actual, 'keys and non model values should be preserved');
        self::assertNull(Utility::entityIdToModel(null));
        self::assertSame([], Utility::entityIdToModel([]));
    }

    public function testModelToId(): void
    {
        $input = [
            'entity' => new class() implements Model {
                public function getId(): ?int
                {
                    return 123456;
                }
            },
            4 => 1,
        ];

        $actual = Utility::modelToId($input);

        $expected = $input;
        $expected['entity'] = 123456;

        self::assertSame($expected, $actual, 'models must be replaced by their ids, other values should be preserved');
    }

    private function createArray(): array
    {
        $object1 = new class() {
        };

        $object2 = new class() {
        };

        return [
            $object1,
            3,
            $object2,
            3,
            $object1,
            2,
            '2',
        ];
    }

    public function testUnique(): void
    {
        $array = $this->createArray();
        $actual = Utility::unique($array);

        $expected = [
            $array[0],
            $array[1],
            $array[2],
            $array[5],
            $array[6],
        ];

        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider providerNativeUniqueWillThrowWithOurTestObject
     */
    public function testNativeUniqueWillThrowWithOurTestObject(int $flag): void
    {
        try {
            $foo = array_unique($this->createArray(), $flag);
        } catch (Throwable $e) {
            self::assertStringStartsWith('Object of class class@anonymous could not be converted to ', $e->getMessage());
        }
    }

    public function providerNativeUniqueWillThrowWithOurTestObject(): iterable
    {
        yield [SORT_REGULAR];
        yield [SORT_NUMERIC];
        yield [SORT_STRING];
        yield [SORT_LOCALE_STRING];
    }

    /**
     * @dataProvider providerQuoteArray
     */
    public function testQuoteArray(array $input, string $expected): void
    {
        self::assertSame($expected, Utility::quoteArray($input));
    }

    public function providerQuoteArray(): iterable
    {
        yield [[1, 2], "'1', '2'"];
        yield [['foo bar', 'baz'], "'foo bar', 'baz'"];
        yield [[], ''];
        yield [[''], "''"];
        yield [[false], ''];
        yield [[null], ''];
        yield [[0], "'0'"];
        yield [[true], "'1'"];
        yield [[1.23], "'1.23'"];
    }
}

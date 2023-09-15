<?php

declare(strict_types=1);

namespace EcodevTests\Felix\ORM\Query;

use Ecodev\Felix\ORM\Query\NativeIn;
use EcodevTests\Felix\Traits\TestWithTypes;
use PHPUnit\Framework\TestCase;

class NativeInTest extends TestCase
{
    use TestWithTypes;

    public static function providerNativeIn(): iterable
    {
        yield 'normal with string' => [
            'SELECT u.id FROM EcodevTests\Felix\Blog\Model\User u WHERE ' . NativeIn::dql('u.id', "SELECT '123'"),
            "SELECT u0_.id AS id_0 FROM user u0_ WHERE u0_.id IN (SELECT '123') = 1",
        ];

        yield 'normal with escape' => [
            'SELECT c.id FROM EcodevTests\Felix\Blog\Model\User c WHERE ' . NativeIn::dql('c.id', "SELECT '1\t2\n3'"),
            "SELECT u0_.id AS id_0 FROM user u0_ WHERE u0_.id IN (SELECT '1\t2\n3') = 1",
        ];
        yield 'normal with double string' => [
            'SELECT c.id FROM EcodevTests\Felix\Blog\Model\User c WHERE ' . NativeIn::dql('c.id', 'SELECT "123"'),
            'SELECT u0_.id AS id_0 FROM user u0_ WHERE u0_.id IN (SELECT "123") = 1',
        ];
        yield 'negative with string' => [
            'SELECT u.id FROM EcodevTests\Felix\Blog\Model\User u WHERE ' . NativeIn::dql('u.id', "SELECT '123'", true),
            "SELECT u0_.id AS id_0 FROM user u0_ WHERE u0_.id NOT IN (SELECT '123') = 1",
        ];
        yield 'complex' => [
            'SELECT u.id FROM EcodevTests\Felix\Blog\Model\User u WHERE ' . NativeIn::dql('u.id', 'SELECT post.user_id FROM post'),
            'SELECT u0_.id AS id_0 FROM user u0_ WHERE u0_.id IN (SELECT post.user_id FROM post) = 1',
        ];
        yield 'multiline' => [
            'SELECT u.id FROM EcodevTests\Felix\Blog\Model\User u WHERE ' . NativeIn::dql(
                'u.id',
                <<<SQL
                    SELECT post.user_id
                    FROM post
                    SQL
            ),
            <<<SQL
                SELECT u0_.id AS id_0 FROM user u0_ WHERE u0_.id IN (SELECT post.user_id
                FROM post) = 1
                SQL,
        ];
        yield 'alias' => [
            'SELECT u.id AS my_alias FROM EcodevTests\Felix\Blog\Model\User u WHERE ' . NativeIn::dql('my_alias', "SELECT '123'"),
            "SELECT u0_.id AS id_0 FROM user u0_ WHERE id_0 IN (SELECT '123') = 1",

        ];
    }

    /**
     * @dataProvider providerNativeIn
     */
    public function testNativeIn(string $dql, string $expected): void
    {
        $query = $this->entityManager->createQuery($dql);
        $actual = $query->getSQL();

        self::assertSame($expected, $actual);
    }
}

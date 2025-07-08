<?php

declare(strict_types=1);

namespace EcodevTests\Felix\ORM\Query\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Ecodev\Felix\ORM\Query\Filter\AclFilter;
use EcodevTests\Felix\Blog\Model\Post;
use EcodevTests\Felix\Blog\Model\User;
use EcodevTests\Felix\Traits\TestWithEntityManager;
use Exception;
use PHPUnit\Framework\TestCase;

final class AclFilterTest extends TestCase
{
    use TestWithEntityManager;

    /**
     * @dataProvider providerFilter
     *
     * @param class-string $class
     */
    public function testFilter(bool $anonymous, string $class, string $expected): void
    {
        $classMetadataFactory = $this->entityManager->getMetadataFactory();
        /** @var ClassMetadata $targetEntity */
        $targetEntity = $classMetadataFactory->getMetadataFor($class);
        $filter = new AclFilter($this->entityManager);

        $filter->setUser($anonymous ? null : new User());
        $actual = $filter->addFilterConstraint($targetEntity, 'test');

        if ($expected === '') {
            self::assertSame($expected, $actual);
        } else {
            self::assertStringStartsWith($expected, $actual);
        }
    }

    public static function providerFilter(): iterable
    {
        return [
            'users are accessible to anonymous' => [
                false,
                User::class,
                '',
            ],
            'users are accessible to any users' => [
                true,
                User::class,
                '',
            ],
            'posts are accessible to anonymous' => [
                false,
                Post::class,
                '',
            ],
            'posts are accessible to any other users' => [
                true,
                Post::class,
                'test.id IN (SELECT',
            ],
        ];
    }

    public function testDeactivable(): void
    {
        /** @var ClassMetadata $targetEntity */
        $targetEntity = $this->entityManager->getMetadataFactory()->getMetadataFor(Post::class);
        $filter = new AclFilter($this->entityManager);

        self::assertNotSame('', $filter->addFilterConstraint($targetEntity, 'test'), 'enabled by default');

        $filter->runWithoutAcl(function () use ($filter, $targetEntity): void {
            $this->assertSame('', $filter->addFilterConstraint($targetEntity, 'test'), 'can disable');

            $filter->runWithoutAcl(function () use ($filter, $targetEntity): void {
                $this->assertSame('', $filter->addFilterConstraint($targetEntity, 'test'), 'can disable one more time and still disabled');
            });

            $this->assertSame('', $filter->addFilterConstraint($targetEntity, 'test'), 'enable once and still disabled');
        });

        self::assertNotSame('', $filter->addFilterConstraint($targetEntity, 'test'), 'enabled a second time and really enabled');
    }

    public function testDisableForever(): void
    {
        /** @var ClassMetadata $targetEntity */
        $targetEntity = $this->entityManager->getMetadataFactory()->getMetadataFor(Post::class);
        $filter = new AclFilter($this->entityManager);

        self::assertNotSame('', $filter->addFilterConstraint($targetEntity, 'test'), 'enabled by default');

        $filter->disableForever();
        self::assertSame('', $filter->addFilterConstraint($targetEntity, 'test'), 'disabled forever');

        $filter->runWithoutAcl(function () use ($filter, $targetEntity): void {
            $this->assertSame('', $filter->addFilterConstraint($targetEntity, 'test'), 'also disabled forever anyway');
        });

        self::assertSame('', $filter->addFilterConstraint($targetEntity, 'test'), 'still disabled forever');
    }

    public function testExceptionWillReEnableFilter(): void
    {
        /** @var ClassMetadata $targetEntity */
        $targetEntity = $this->entityManager->getMetadataFactory()->getMetadataFor(Post::class);
        $filter = new AclFilter($this->entityManager);

        try {
            $filter->runWithoutAcl(function (): never {
                throw new Exception();
            });
        } catch (Exception) {
        }

        self::assertNotSame('', $filter->addFilterConstraint($targetEntity, 'test'), 'enabled even after exception');
    }

    public function testFilterCollectionHashMustChangeWheneverTheUserIsChanged(): void
    {
        $collection = $this->entityManager->getFilters();
        self::assertSame('', $collection->getHash());

        $collection->enable(AclFilter::class);
        self::assertSame('Ecodev\Felix\ORM\Query\Filter\AclFiltera:0:{}', $collection->getHash());

        $user = $this->createMock(\Ecodev\Felix\Model\User::class);
        $user->method('getId')->willReturn(123);

        /** @var AclFilter $filter */
        $filter = $collection->getFilter(AclFilter::class);
        $filter->setUser($user);
        self::assertSame('Ecodev\Felix\ORM\Query\Filter\AclFiltera:1:{s:4:"user";a:3:{s:5:"value";i:123;s:4:"type";s:7:"integer";s:7:"is_list";b:0;}}', $collection->getHash());

        $filter->setUser(null);
        self::assertSame('Ecodev\Felix\ORM\Query\Filter\AclFiltera:1:{s:4:"user";a:3:{s:5:"value";N;s:4:"type";E:34:"Doctrine\DBAL\ParameterType:STRING";s:7:"is_list";b:0;}}', $collection->getHash());
    }
}

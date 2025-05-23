<?php

declare(strict_types=1);

namespace Ecodev\Felix\Testing\ORM;

use Doctrine\ORM\Tools\SchemaValidator;
use PHPUnit\Framework\TestCase;

class MappingTest extends TestCase
{
    public function testMappingIsValid(): void
    {
        $em = _em();
        $validator = new SchemaValidator($em);

        $result = '';
        $errors = $validator->validateMapping();
        foreach ($errors as $className => $errorMessages) {
            $result .= $className . ':' . PHP_EOL;
            foreach ($errorMessages as $e) {
                $result .= $e . PHP_EOL;
            }
            $result .= PHP_EOL;
        }

        self::assertSame('', trim($result), 'should have valid mapping');
    }

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState disabled
     */
    public function testMappingIsSync(): void
    {
        $em = _em();
        $validator = new SchemaValidator($em);
        $updates = $validator->getUpdateSchemaList();
        $updates = array_filter($updates, fn (string $a) => $a !== 'DROP TABLE doctrine_migration_versions');

        self::assertSame([], $updates, 'database should be in sync with mapping');
    }
}

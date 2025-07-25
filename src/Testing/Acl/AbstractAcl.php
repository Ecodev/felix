<?php

declare(strict_types=1);

namespace Ecodev\Felix\Testing\Acl;

use Ecodev\Felix\Acl\Acl;
use PHPUnit\Framework\TestCase;

abstract class AbstractAcl extends TestCase
{
    abstract protected function createAcl(): Acl;

    /**
     * @dataProvider providerRole
     */
    final public function testRole(string $role): void
    {
        $acl = $this->createAcl();
        $actual = $acl->show($role, false);

        $file = "tests/data/acl/$role.php";
        $logFile = "logs/$file";

        $dir = dirname($logFile);
        @mkdir($dir, 0o777, true);
        $serialized = ve($actual, true);
        file_put_contents(
            $logFile,
            <<<STRING
                <?php

                declare(strict_types=1);

                return $serialized;

                STRING
        );

        self::assertFileExists($file, 'Expected file must exist on disk, fix it with: cp ' . $logFile . ' ' . $file);

        $expected = require $file;
        self::assertTrue($expected === $actual, 'File content does not match, compare with: meld ' . $file . ' ' . $logFile);
    }

    abstract public function providerRole(): iterable;

    final public function testEverythingIsTranslated(): void
    {
        $acl = $this->createAcl();
        $roles = $acl->getRoles();
        $config = $acl->show(reset($roles));
        self::assertIsArray($config);
    }
}

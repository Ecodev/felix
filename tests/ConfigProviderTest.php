<?php

declare(strict_types=1);

namespace EcodevTests\Felix;

use Ecodev\Felix\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testConfigProvider(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();
        self::assertIsArray($config);
        self::assertArrayHasKey('dependencies', $config);
        self::assertIsArray($config['dependencies']);
    }
}

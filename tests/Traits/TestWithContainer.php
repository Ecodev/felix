<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Traits;

use Ecodev\Felix\ConfigProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ServiceManager;

/**
 * Trait to easily set up container.
 */
trait TestWithContainer
{
    protected function tearDown(): void
    {
        global $container;
        $container = null;
    }

    /**
     * Create the global `$container` variable with Felix default configuration.
     */
    private function createDefaultFelixContainer(): void
    {
        $defaultFelixConfiguration = new ConfigAggregator([
            ConfigProvider::class,
        ]);

        $this->createContainer($defaultFelixConfiguration);
    }

    /**
     * Create the global `$container` variable with the given configuration.
     */
    private function createContainer(ConfigAggregator $aggregator): void
    {
        $config = $aggregator->getMergedConfig();
        $dependencies = $config['dependencies'];
        $dependencies['services']['config'] = $config;

        global $container;
        $container = new ServiceManager($dependencies);
    }
}

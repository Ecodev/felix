<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Traits;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Ecodev\Felix\ORM\Query\NativeIn;
use Laminas\ServiceManager\ServiceManager;

/**
 * Trait to easily set up a dummy entity manager.
 */
trait TestWithEntityManager
{
    private EntityManager $entityManager;

    public function setUp(): void
    {
        // Create the entity manager
        $config = ORMSetup::createAnnotationMetadataConfiguration([__DIR__ . '/Blog/Model'], true);
        $config->addCustomNumericFunction('native_in', NativeIn::class);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER, true));

        $connection = [
            'wrapperClass' => MariaDbQuotingConnection::class,
            'url' => 'sqlite:///:memory:',
        ];

        $this->entityManager = EntityManager::create($connection, $config);

        global $container;
        $container = new ServiceManager([
            'factories' => [
                EntityManager::class => fn () => $this->entityManager,
            ],
        ]);
    }

    public function tearDown(): void
    {
        global $container;
        $container = null;
    }
}

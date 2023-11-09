<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Enum;

use BackedEnum;
use Exception;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Will create GraphQL Enums for a short name or a FQCN of a PHP native backed enum.
 *
 * The PHP enum must live in `Application\Enum` namespace.
 */
class EnumAbstractFactory implements AbstractFactoryInterface
{
    /**
     * @var array<class-string<BackedEnum>, PhpEnumType>
     */
    private array $cache = [];

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $class = $this->getClass($requestedName);

        return (bool) $class;
    }

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $class = $this->getClass($requestedName);
        if (!$class) {
            throw new Exception('Cannot create a PhpEnumType for a name not matching a backed enum: ' . $requestedName);
        }

        // Share the same instance between short name and FQCN
        if (!array_key_exists($class, $this->cache)) {
            $this->cache[$class] = new PhpEnumType($class);
        }

        return $this->cache[$class];
    }

    /**
     * @return null|class-string<BackedEnum>
     */
    private function getClass(string $requestedName): ?string
    {
        $possibilities = [
            $requestedName,
            'Application\Enum\\' . $requestedName,
        ];

        foreach ($possibilities as $class) {
            if (class_exists($class) && is_a($class, BackedEnum::class, true)) {
                return $class;
            }
        }

        return null;
    }
}

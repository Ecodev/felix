<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Output;

use Ecodev\Felix\Model\Model;
use Exception;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Create a Pagination type for the entity extracted from name.
 *
 * For example, if given "ActionPagination", it will create a Pagination
 * type for the Action entity.
 */
class PaginationTypeFactory implements AbstractFactoryInterface
{
    private const PATTERN = '~^(.*)Pagination$~';

    public function canCreate(ContainerInterface $container, $requestedName): bool
    {
        $class = $this->getClass($requestedName);

        return $class && is_a($class, Model::class, true);
    }

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): PaginationType
    {
        $class = $this->getClass($requestedName);
        if (!$class) {
            throw new Exception('Cannot create a PaginationType for a name not matching a model: ' . $requestedName);
        }

        $extraFields = $this->getExtraFields($class);

        $type = new PaginationType($class, $extraFields);

        return $type;
    }

    /**
     * @return null|class-string
     */
    private function getClass(string $requestedName): ?string
    {
        if (!preg_match(self::PATTERN, $requestedName, $m)) {
            return null;
        }

        $possibilities = [
            'Application\Model\\' . $m[1],
            'Application\Model\Relation\\' . $m[1],
        ];

        foreach ($possibilities as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * GraphQL configuration for extra fields, typically for aggregated fields only available on some entities.
     */
    protected function getExtraFields(string $class): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Input\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Ecodev\Felix\Api\Exception;
use Ecodev\Felix\Format;
use GraphQL\Doctrine\Definition\Operator\AbstractOperator;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\LeafType;
use ReflectionClass;

abstract class SearchOperatorType extends AbstractOperator
{
    protected function getConfiguration(LeafType $leafType): array
    {
        return [
            'fields' => [
                [
                    'name' => 'value',
                    'type' => self::nonNull($leafType),
                ],
            ],
        ];
    }

    public function getDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, ?array $args): string
    {
        if (!$args) {
            return '';
        }

        $words = Format::splitSearchTerms($args['value']);
        if (!$words) {
            return '';
        }

        $scalarFields = $this->getSearchableFields($metadata, $alias);
        $fieldsOnJoin = $this->getSearchableFieldsOnJoin($uniqueNameFactory, $metadata, $queryBuilder, $alias);
        $allFields = array_merge($scalarFields, $fieldsOnJoin);

        return $this->buildSearchDqlCondition($uniqueNameFactory, $metadata, $queryBuilder, $allFields, $words);
    }

    abstract protected function getSearchableFieldsWhitelist(ClassMetadata $metadata): array;

    private function getSearchableFields(ClassMetadata $metadata, string $alias): array
    {
        $whitelistedFields = $this->getSearchableFieldsWhitelist($metadata);

        // Find most textual fields for the entity
        $fields = [];
        foreach ($metadata->fieldMappings as $mapping) {
            if (in_array($mapping->fieldName, $whitelistedFields, true)) {
                $fieldName = $mapping->fieldName;
                $field = $alias . '.' . $fieldName;

                $fields[] = $this->fieldToDql($metadata->getReflectionClass(), $fieldName, $field);
            }
        }

        return $fields;
    }

    /**
     * Optionally wrap the `$fieldAlias` in a custom DQL function. Typically useful to search correctly in i18n fields.
     */
    protected function fieldToDql(ReflectionClass $className, string $fieldName, string $fieldAlias): string
    {
        return $fieldAlias;
    }

    /**
     * Map one class to one joined entity that is searchable.
     *
     * This list should be kept as small as possible
     *
     * @return string[][]
     */
    abstract protected function getSearchableJoinedEntities(): array;

    /**
     * Return searchable fields from a joined entity.
     *
     * This should be avoided if possible to instead only search in the original entity itself.
     */
    private function getSearchableFieldsOnJoin(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias): array
    {
        $config = $this->getSearchableJoinedEntities();

        $fields = [];
        foreach ($config as $class => $fieldNames) {
            if (is_a($metadata->getName(), $class, true)) {
                foreach ($fieldNames as $fieldName) {
                    $fields = array_merge(
                        $fields,
                        $this->searchOnJoinedEntity($uniqueNameFactory, $metadata, $queryBuilder, $alias, $fieldName),
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Add a join and return searchable fields in order to search on a joined entity.
     *
     * @return string[]
     */
    private function searchOnJoinedEntity(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $fieldName): array
    {
        $association = $metadata->getAssociationMapping($fieldName);
        $targetEntity = $association->targetEntity;

        $joinedMetadata = $queryBuilder->getEntityManager()->getMetadataFactory()->getMetadataFor($targetEntity);
        $joinedAlias = $uniqueNameFactory->createAliasName($targetEntity);

        $queryBuilder->leftJoin($alias . '.' . $fieldName, $joinedAlias, Join::WITH);

        return $this->getSearchableFields($joinedMetadata, $joinedAlias);
    }

    /**
     * Return a DQL condition to search each of the words in any of the fields.
     */
    final protected function buildSearchDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, array $fields, array $words): string
    {
        if (!$fields) {
            throw new Exception('Cannot find fields to search on for entity ' . $metadata->name);
        }

        $wordWheres = [];

        foreach ($words as $word) {
            $parameterName = $uniqueNameFactory->createParameterName();

            $fieldWheres = [];
            foreach ($fields as $field) {
                $fieldWheres[] = $field . ' LIKE :' . $parameterName;
            }

            $wordWheres[] = '(' . implode(' OR ', $fieldWheres) . ')';
            $queryBuilder->setParameter($parameterName, '%' . $word . '%');
        }

        return implode(' AND ', $wordWheres);
    }
}

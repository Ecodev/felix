<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Ecodev\Felix\DBAL\Types\EnumType;

/**
 * When we update our enum values in PHP (native or non-native), a DB migration will be created automatically.
 */
class EnumAutoMigrator
{
    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs): void
    {
        foreach ($eventArgs->getSchema()->getTables() as $table) {
            foreach ($table->getColumns() as $column) {
                $type = $column->getType();
                if ($type instanceof EnumType) {
                    $hash = md5($type->getQuotedPossibleValues());
                    $column->setComment("(FelixEnum:$hash)");
                }
            }
        }
    }
}

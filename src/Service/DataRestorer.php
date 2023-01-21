<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\NamingStrategy;
use Exception;

class DataRestorer
{
    private const NULL_TOKEN = 'MY_SECRET_NULL_TOKEN';

    private array $restoreQueries = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly NamingStrategy $namingStrategy,
        private readonly string $databaseToRestore,
        private readonly string $tableToRestore,
        private readonly array $idsToRestore,
    ) {
    }

    /**
     * This will connect to the given backup database to generate the SQL queries necessary to restore the given data.
     * Those queries must then be run manually on production database.
     *
     * The restored data include:
     *
     * - the objects themselves (will generate `LOAD DATA`)
     * - the oneToMany relations that might have been set NULL (will generate `UPDATE`)
     * - the manyToMany relations that might have been deleted (will generate `LOAD DATA`)
     *
     * However, this is a **best effort** so we `IGNORE` failure when restoring things.
     * So **after a restore it is possible that foreign key don't match**, unfortunately, and
     * that some data and relations are not restored due to new data inserted after the backup
     * conflicting with restored data.
     */
    public function generateQueriesToRestoreDeletedData(): void
    {
        $this->restoreQueries = [];

        $this->restoreTableData();
        $this->restoreRelations();

        if (count($this->restoreQueries)) {
            $fileName = 'restore.sql';
            file_put_contents($fileName, implode(PHP_EOL, $this->restoreQueries));

            echo <<<STRING
                
                # TODO manually 
                
                1. Copy all `restore*` files to production server
                2. On production server, run a command similar to:

                mariadb < $fileName

                STRING;
        }
    }

    /**
     * @return array{0: int, 1: array<string>}
     */
    private function exportQueryToCsv(string $query, string $fileName): array
    {
        $result = $this->connection->executeQuery($query);

        $buffer = null;
        $firstRow = true;
        $count = 0;
        $columnNames = [];
        while ($row = $result->fetchAssociative()) {
            if ($firstRow) {
                $buffer = fopen($fileName, 'w+b');
                if ($buffer === false) {
                    throw new Exception('Cannot write to ' . $fileName);
                }
                $columnNames = array_keys($row);
                fputcsv($buffer, $columnNames);
                $firstRow = false;
            }

            foreach ($row as $k => $v) {
                if ($v === null) {
                    $row[$k] = self::NULL_TOKEN;
                }
            }

            if ($buffer) {
                $line = $this->toCsv($row);
                fwrite($buffer, $line);
            }

            ++$count;
        }

        if ($buffer) {
            fclose($buffer);
        }

        return [$count, $columnNames];
    }

    private function toCsv(array $fields): string
    {
        $fp = fopen('php://temp', 'r+b');
        if ($fp === false) {
            throw new Exception('Cannot write in memory');
        }

        fputcsv($fp, $fields);
        rewind($fp);
        $data = stream_get_contents($fp);
        if ($data === false) {
            throw new Exception('Cannot read from memory');
        }

        fclose($fp);

        return str_replace(self::NULL_TOKEN, 'NULL', $data);
    }

    /**
     *  Export the result for each table to individual CSV files.
     */
    private function restoreTableData(): void
    {
        $tableSelects = $this->getTablesToRestore();

        $this->restoreQueries[] = 'SET FOREIGN_KEY_CHECKS = 0;';

        foreach ($tableSelects as $t => $queries) {
            foreach ($queries as $i => $query) {
                $fileName = 'restore-' . $t . '-' . $i . '.csv';
                [$count, $columnNames] = $this->exportQueryToCsv($query, $fileName);

                if ($count) {
                    $columns = implode(', ', array_map(fn ($name) => $this->connection->quoteIdentifier($name), $columnNames));
                    $this->restoreQueries[] = <<<STRING
                        LOAD DATA LOCAL INFILE '$fileName' INTO TABLE `$t` FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' IGNORE 1 LINES ($columns);
                        STRING;
                    echo $count . ' records exported in ' . $fileName . PHP_EOL;
                }
            }
        }

        $this->restoreQueries[] = 'SET FOREIGN_KEY_CHECKS = 1;';
    }

    private function restoreRelations(): void
    {
        // Generate UPDATE queries to recover the values that were erased by the SET NULL FK constraint
        /** @var array<array<string, string>> $foreignKeys */
        $foreignKeys = $this->connection->fetchAllAssociative(
            "SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA='$this->databaseToRestore' AND REFERENCED_TABLE_NAME='$this->tableToRestore';"
        );

        foreach ($foreignKeys as $foreignKey) {
            foreach ($this->idsToRestore as $id) {
                if (preg_match('/^(source|target)(.+)$/', $foreignKey['COLUMN_NAME'], $m)) {
                    // N-N relationship between 2 objects of the same type (ex: `document_document`)
                    $primaryKey = ($m[1] === 'source') ? 'target' . $m[2] : 'source' . $m[2];
                } elseif (preg_match('/^([^_]+)_[^_]+$/', $foreignKey['TABLE_NAME'], $m)) {
                    /** @var class-string $className */
                    $className = $m[1];
                    $primaryKey = $this->namingStrategy->joinKeyColumnName($className);
                } else {
                    $primaryKey = 'id';
                }
                $query = <<<EOH
                        SELECT CONCAT("UPDATE IGNORE `${foreignKey['TABLE_NAME']}` SET ${foreignKey['COLUMN_NAME']}=$id WHERE ${primaryKey} IN (",GROUP_CONCAT(DISTINCT ${primaryKey} SEPARATOR ','),");")
                        FROM `${foreignKey['TABLE_NAME']}`
                        WHERE ${foreignKey['COLUMN_NAME']}=$id
                        GROUP BY ${foreignKey['COLUMN_NAME']};
                    EOH;
                $result = $this->connection->fetchOne($query);
                if ($result) {
                    $this->restoreQueries[] = $result;
                }
            }
        }
    }

    /**
     * @return array<string, array<string>>
     */
    private function getTablesToRestore(): array
    {
        $tableSelects = [];

        // Query to export the main deleted records
        $tableSelects[$this->tableToRestore][] = "SELECT * FROM `$this->databaseToRestore`.`$this->tableToRestore` WHERE id IN (" . implode(',', $this->idsToRestore) . ')';

        // Queries to export the records in other tables that were deleted via the CASCADE FK constraint
        $foreignKeysQuery = <<<EOH
            SELECT DISTINCT(u.TABLE_NAME),u.COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE as u INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS as c
            WHERE c.CONSTRAINT_SCHEMA='$this->databaseToRestore' AND c.REFERENCED_TABLE_NAME='$this->tableToRestore' AND c.DELETE_RULE='CASCADE' AND c.CONSTRAINT_NAME=u.CONSTRAINT_NAME
            EOH;

        /** @var array<array<string, string>> $foreignKeys */
        $foreignKeys = $this->connection->fetchAllAssociative($foreignKeysQuery);

        foreach ($foreignKeys as $foreignKey) {
            $tableSelects[$foreignKey['TABLE_NAME']][] = "SELECT * FROM `$this->databaseToRestore`.`${foreignKey['TABLE_NAME']}` WHERE ${foreignKey['COLUMN_NAME']} IN (" . implode(',', $this->idsToRestore) . ')';
        }

        return $tableSelects;
    }
}

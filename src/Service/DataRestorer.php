<?php

namespace Application\Utility;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\NamingStrategy;

class DataRestorer
{
    private const NULL_TOKEN = 'MY_SECRET_NULL_TOKEN';

    private array $restoreQueries = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly NamingStrategy $namingStrategy,
        private readonly string $backupDatabase,
        private readonly string $tableToRestore,
        private readonly array $idsToRestore,
    )
    {
    }

    /**
     * Generate the queries to be run on a backup database, that will generate queries
     * to update/insert erased items in tables referencing them (foreign key constraints).
     */
    public function generateQueriesToRestoreDeletedData(): void
    {
        $this->restoreQueries = [];

        $this->restoreTableData();
        $this->restoreRelations();

        if (count($this->restoreQueries)) {
            $fileName = 'restore.sql';
            file_put_contents($fileName, implode(PHP_EOL, $this->restoreQueries));
            echo PHP_EOL . '### Update queries to execute to production database ###' . PHP_EOL . PHP_EOL;
            echo 'mariadb ' . Tools::getMysqlArgs() . ' < ' . $fileName . PHP_EOL;
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
                $columnNames = array_keys($row);
                fputcsv($buffer, $columnNames);
                $firstRow = false;
            }

            foreach ($row as $k => $v) {
                if ($v === null) {
                    $row[$k] = self::NULL_TOKEN;
                }
            }
            $line = $this->toCsv($row);
            fwrite($buffer, $line);

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
        fputcsv($fp, $fields);
        rewind($fp);
        $data = stream_get_contents($fp);
        fclose($fp);

        return str_replace(self::NULL_TOKEN, 'NULL', ($data));
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
        $foreignKeys = $this->connection->fetchAllAssociative(
            "SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA='$this->backupDatabase' AND REFERENCED_TABLE_NAME='$this->tableToRestore';"
        );

        foreach ($foreignKeys as $foreignKey) {
            foreach ($this->idsToRestore as $id) {
                if (preg_match('/^(source|target)(.+)$/', $foreignKey['COLUMN_NAME'], $m)) {
                    // N-N relationship between 2 objects of the same type (ex: `document_document`)
                    $primaryKey = ($m[1] === 'source') ? 'target' . $m[2] : 'source' . $m[2];
                } elseif (preg_match('/^([^_]+)_[^_]+$/', $foreignKey['TABLE_NAME'], $m)) {
                    $primaryKey = $this->namingStrategy->joinKeyColumnName($m[1]);
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
        $tableSelects[$this->tableToRestore][] = "SELECT * FROM `$this->backupDatabase`.`$this->tableToRestore` WHERE id IN (" . implode(',', $this->idsToRestore) . ')';

        // Queries to export the records in other tables that were deleted via the CASCADE FK constraint
        $foreignKeysQuery = <<<EOH
            SELECT DISTINCT(u.TABLE_NAME),u.COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE as u INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS as c
            WHERE c.CONSTRAINT_SCHEMA='$this->backupDatabase' AND c.REFERENCED_TABLE_NAME='$this->tableToRestore' AND c.DELETE_RULE='CASCADE' AND c.CONSTRAINT_NAME=u.CONSTRAINT_NAME
            EOH;

        $foreignKeys = $this->connection->fetchAllAssociative($foreignKeysQuery);

        foreach ($foreignKeys as $foreignKey) {
            $tableSelects[$foreignKey['TABLE_NAME']][] = "SELECT * FROM `$this->backupDatabase`.`${foreignKey['TABLE_NAME']}` WHERE ${foreignKey['COLUMN_NAME']} IN (" . implode(',', $this->idsToRestore) . ')';
        }

        return $tableSelects;
    }
}

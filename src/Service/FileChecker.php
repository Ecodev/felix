<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Doctrine\DBAL\Connection;
use Exception;

/**
 * Check missing files on disk and non-needed files on disk.
 *
 * It is up to the user to then take appropriate action based on that information.
 */
class FileChecker
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * Print the result.
     *
     * @param array<string, string> $config must be $table => $basePath
     */
    public function check(array $config): void
    {
        $filesInDb = $this->fetchFromDb($config);
        $filesOnDisk = $this->readDisk($config);

        $missingFiles = array_diff($filesInDb, $filesOnDisk);
        $unneededFiles = array_diff($filesOnDisk, $filesInDb);

        $this->printFiles('List of missing files on disk:', $missingFiles);
        $this->printFiles('List of unneeded files on disk:', $unneededFiles);

        echo '
Total files in DB     : ' . count($filesInDb) . '
Total files on disk   : ' . count($filesOnDisk) . '
Missing files on disk : ' . count($missingFiles) . '
Unneeded files on disk: ' . count($unneededFiles) . '
';
    }

    /**
     * @param array<string, string> $config
     */
    private function fetchFromDb(array $config): array
    {
        $queries = [];
        foreach ($config as $table => $basePath) {
            $q = 'SELECT DISTINCT CONCAT(' . $this->connection->quote($basePath) . ', filename) FROM ' . $this->connection->quoteIdentifier($table) . ' WHERE filename != "" ORDER BY filename';
            $queries[] = '(' . $q . ')';
        }

        $query = implode(' UNION ', $queries);

        return $this->connection->executeQuery($query)->fetchFirstColumn();
    }

    /**
     * @param array<string, string> $config
     */
    private function readDisk(array $config): array
    {
        $files = [];

        foreach ($config as $basePath) {
            $filesFound = glob($basePath . '*');
            if ($filesFound === false) {
                throw new Exception('Could not glob path: ' . $basePath);
            }

            $filesFound = array_filter($filesFound, 'is_file');

            $files = [...$files, ...$filesFound];
        }

        sort($files);

        return $files;
    }

    /**
     * Print a list of files if non-empty.
     */
    private function printFiles(string $title, array $files): void
    {
        if (!$files) {
            return;
        }

        echo $title . PHP_EOL . PHP_EOL;

        foreach ($files as $file) {
            echo '    ' . escapeshellarg($file) . PHP_EOL;
        }
        echo PHP_EOL;
    }
}

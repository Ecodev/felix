<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Exception;

/**
 * Tool to reload the entire local database from remote database for a given site
 *
 * Requirements:
 *
 * - ssh access to remote server (via ~/.ssh/config)
 * - both local and remote sites must be accessible via: /sites/MY_SITE
 */
abstract class AbstractDatabase
{
    /**
     * Dump data from database on $remote server
     */
    private static function dumpDataRemotely(string $remote, string $dumpFile): void
    {
        $sshCmd = <<<STRING
                    ssh $remote "cd /sites/$remote/ && php7.4 bin/dump-data.php $dumpFile"
STRING;

        echo "dumping data $dumpFile on $remote...\n";
        self::executeLocalCommand($sshCmd);
    }

    /**
     * Dump data from database
     */
    final public static function dumpData(string $dumpFile): void
    {
        $mysqlArgs = self::getMysqlArgs();

        echo "dumping $dumpFile...\n";
        $dumpCmd = "mysqldump -v $mysqlArgs | sed 's/DEFINER=[^*]*\\*/\\*/g' | gzip > $dumpFile";
        self::executeLocalCommand($dumpCmd);
    }

    /**
     * Copy a file from $remote
     */
    private static function copyFile(string $remote, string $dumpFile): void
    {
        $copyCmd = <<<STRING
                    rsync -avz --progress $remote:$dumpFile $dumpFile
STRING;

        echo "copying dump to $dumpFile ...\n";
        self::executeLocalCommand($copyCmd);
    }

    /**
     * Load SQL dump in local database
     */
    final public static function loadData(string $dumpFile): void
    {
        $mysqlArgs = self::getMysqlArgs();
        $dumpFile = self::absolutePath($dumpFile);

        echo "loading dump $dumpFile...\n";
        $database = self::getDatabaseName();
        self::executeLocalCommand(PHP_BINARY . ' ./vendor/bin/doctrine orm:schema-tool:drop --ansi --full-database --force');
        self::executeLocalCommand("gunzip -c \"$dumpFile\" | sed  's/ALTER DATABASE `[^`]*`/ALTER DATABASE `$database`/g' | mysql $mysqlArgs");
        self::executeLocalCommand(PHP_BINARY . ' ./vendor/bin/doctrine-migrations --ansi migrations:migrate --no-interaction');
        self::loadTriggers();
        self::loadTestUsers();
    }

    private static function getDatabaseName(): string
    {
        $dbConfig = _em()->getConnection()->getParams();

        return $dbConfig['dbname'];
    }

    private static function getMysqlArgs(): string
    {
        $dbConfig = _em()->getConnection()->getParams();

        $host = $dbConfig['host'] ?? 'localhost';
        $username = $dbConfig['user'];
        $database = $dbConfig['dbname'];
        $password = $dbConfig['password'];
        $port = $dbConfig['port'] ?? 3306;

        // It's possible to have no password at all
        $password = $password ? '-p' . $password : '';

        return "--user=$username $password --host=$host --port=$port $database";
    }

    final public static function loadRemoteData(string $remote): void
    {
        $dumpFile = "/tmp/$remote." . exec('whoami') . '.backup.sql.gz';
        self::dumpDataRemotely($remote, $dumpFile);
        self::copyFile($remote, $dumpFile);
        self::loadData($dumpFile);

        echo "database updated\n";
    }

    /**
     * Execute a shell command and throw exception if fails
     */
    final public static function executeLocalCommand(string $command): void
    {
        $return_var = null;
        $fullCommand = "$command 2>&1";
        passthru($fullCommand, $return_var);
        if ($return_var) {
            throw new Exception('FAILED executing: ' . $command);
        }
    }

    /**
     * Load test data
     */
    final public static function loadTestData(): void
    {
        self::executeLocalCommand(PHP_BINARY . ' ./vendor/bin/doctrine orm:schema-tool:drop --ansi --full-database --force');
        self::executeLocalCommand(PHP_BINARY . ' ./vendor/bin/doctrine-migrations migrations:migrate --ansi --no-interaction');
        self::loadTriggers();
        self::loadTestUsers();
        self::importFile('tests/data/fixture.sql');
    }

    /**
     * Load triggers
     */
    final public static function loadTriggers(): void
    {
        self::importFile('data/triggers.sql');
    }

    /**
     * Load test users
     */
    private static function loadTestUsers(): void
    {
        self::importFile('tests/data/users.sql');
    }

    /**
     * Import a SQL file into DB
     *
     * This use mysql command, instead of DBAL methods, to allow to see errors if any, and
     * also because it seems trigger creation do not work with DBAL for some unclear reasons.
     */
    final public static function importFile(string $file): void
    {
        $file = self::absolutePath($file);
        $mysqlArgs = self::getMysqlArgs();

        echo 'importing ' . $file . "\n";

        $importCommand = "echo 'SET NAMES utf8mb4;' | cat - $file | mysql $mysqlArgs";

        self::executeLocalCommand($importCommand);
    }

    private static function absolutePath(string $file): string
    {
        $absolutePath = realpath($file);
        if ($absolutePath === false) {
            throw new Exception('Cannot find absolute path for file: ' . $file);
        }

        if (!is_readable($absolutePath)) {
            throw new Exception("Cannot read dump file \"$absolutePath\"");
        }

        return $absolutePath;
    }
}

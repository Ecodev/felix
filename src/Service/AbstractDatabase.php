<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Exception;

/**
 * Tool to reload the entire local database from remote database for a given site.
 *
 * Requirements:
 *
 * - ssh access to remote server (via ~/.ssh/config)
 * - both local and remote sites must be accessible via: /sites/MY_SITE
 */
abstract class AbstractDatabase
{
    /**
     * This is lazy architecture, and we should instead convert the whole class
     * into instantiable service with configuration in constructor, and default
     * factory that get the PHP version from config.
     */
    protected static function getPhp(): string
    {
        return 'php8.2';
    }

    /**
     * Dump data from database on $remote server.
     */
    private static function dumpDataRemotely(string $remote, string $dumpFile): void
    {
        $php = static::getPhp();
        $sshCmd = <<<STRING
            ssh $remote "cd /sites/$remote/ && $php bin/dump-data.php $dumpFile"
            STRING;

        echo "dumping data $dumpFile on $remote...\n";
        self::executeLocalCommand($sshCmd);
    }

    /**
     * Dump data from database.
     */
    final public static function dumpData(string $dumpFile): void
    {
        $mariadbArgs = self::getMariadbArgs();

        echo "dumping $dumpFile...\n";
        $dumpCmd = "mariadb-dump -v $mariadbArgs | LC_CTYPE=C LANG=C sed 's/DEFINER=[^*]*\\*/\\*/g' | gzip > $dumpFile";
        self::executeLocalCommand($dumpCmd);
    }

    /**
     * Copy a file from $remote.
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
     * Load SQL dump in local database.
     */
    final public static function loadData(string $dumpFile): void
    {
        $mariadbArgs = self::getMariadbArgs();
        $dumpFile = self::absolutePath($dumpFile);

        self::confirm('DROP the entire database `' . self::getDatabaseName() . "` and load dump $dumpFile ?");

        echo "loading dump $dumpFile...\n";
        $database = self::getDatabaseName();

        // We close the connection to DB here to avoid a timeout when loading the backup
        // It will be re-opened automatically
        echo "closing connection to DB\n";
        _em()->getConnection()->close();

        self::executeLocalCommand(PHP_BINARY . ' ./bin/doctrine orm:schema-tool:drop --ansi --full-database --force');
        self::executeLocalCommand("gunzip -c \"$dumpFile\" | LC_CTYPE=C LANG=C sed 's/ALTER DATABASE `[^`]*`/ALTER DATABASE `$database`/g' | mariadb $mariadbArgs");
        self::executeLocalCommand(PHP_BINARY . ' ./bin/doctrine migrations:migrate --ansi --no-interaction');
        static::loadTriggers();
        static::loadTestUsers();
    }

    protected static function getDatabaseName(): string
    {
        /** @var array<string,string> $dbConfig */
        $dbConfig = _em()->getConnection()->getParams();

        return $dbConfig['dbname'];
    }

    private static function getMariadbArgs(): string
    {
        /** @var array<string,int|string> $dbConfig */
        $dbConfig = _em()->getConnection()->getParams();

        $host = $dbConfig['host'] ?? 'localhost';
        $username = $dbConfig['user'];
        $database = $dbConfig['dbname'];
        $password = $dbConfig['password'];
        $port = $dbConfig['port'] ?? null;

        if ($port) {
            $port = "--protocol tcp --port=$port";
        } else {
            $port = '--protocol socket';
        }

        // It's possible to have no password at all
        $password = $password ? '-p' . $password : '';

        return "--user=$username $password --host=$host $port $database";
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
     * Execute a shell command and throw exception if fails.
     */
    final public static function executeLocalCommand(string $command): void
    {
        // This allows to specify an application environnement even for commands that are not ours, such as Doctrine one.
        // Thus, this allows us to correctly load test data in a separate test database for OKpilot.
        if (defined('APPLICATION_ENV')) {
            $env = 'APPLICATION_ENV=' . APPLICATION_ENV;
        } else {
            $env = '';
        }

        $return_var = null;
        $fullCommand = "$env $command 2>&1";
        passthru($fullCommand, $return_var);
        if ($return_var) {
            throw new Exception('FAILED executing: ' . $command);
        }
    }

    /**
     * Load test data.
     */
    public static function loadTestData(): void
    {
        self::confirm('DROP the entire database `' . self::getDatabaseName() . '` and load test data ?');

        self::executeLocalCommand(PHP_BINARY . ' ./bin/doctrine orm:schema-tool:drop --ansi --full-database --force');
        self::executeLocalCommand(PHP_BINARY . ' ./bin/doctrine migrations:migrate --ansi --no-interaction');
        static::loadTriggers();
        static::loadTestUsers();
        self::importFile('tests/data/fixture.sql');
    }

    /**
     * Load triggers.
     */
    public static function loadTriggers(): void
    {
        self::importFile('data/triggers.sql');
    }

    /**
     * Load test users.
     */
    protected static function loadTestUsers(): void
    {
        self::importFile('tests/data/users.sql');
    }

    /**
     * Import a SQL file into DB.
     *
     * This use mariadb command, instead of DBAL methods, to allow to see errors if any, and
     * also because it seems trigger creation do not work with DBAL for some unclear reasons.
     */
    final public static function importFile(string $file): void
    {
        $file = self::absolutePath($file);
        $mariadbArgs = self::getMariadbArgs();

        echo 'importing ' . $file . "\n";

        $importCommand = "echo 'SET NAMES utf8mb4;' | cat - $file | mariadb $mariadbArgs";

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

    /**
     * Ask confirmation to user (y/n), unless if we are really sure that we are in local development,
     * or if command argument `--no-interaction` is given.
     *
     * If the user does not confirm, the program will exit immediately.
     */
    public static function confirm(string $question): void
    {
        global $argv;
        $path = getcwd() ?: '';
        if (preg_match('~^/sites/[^/]+\.lan$~', $path) || in_array('--no-interaction', $argv, true)) {
            return;
        }

        echo <<<STRING

            ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️ 

            It looks like this is a\033[01;31m PRODUCTION SITE\033[0m: $path

            $question (y)es (n)o

            ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️    ⚠️ 

            STRING;

        $confirm = readline() ?: '';
        if (!preg_match('/^y(es)?$/', $confirm)) {
            exit(1);
        }
    }
}

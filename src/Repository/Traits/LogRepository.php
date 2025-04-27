<?php

declare(strict_types=1);

namespace Ecodev\Felix\Repository\Traits;

use Cake\Chronos\Chronos;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Ecodev\Felix\Repository\LogRepository as LogRepositoryInterface;
use Monolog\Level;
use Monolog\LogRecord;

trait LogRepository
{
    /**
     * @return EntityManager
     */
    abstract protected function getEntityManager();

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     */
    abstract public function createQueryBuilder(string $alias, string|null $indexBy = null): QueryBuilder;

    /**
     * This should NOT be called directly, instead use `_log()` to log stuff.
     */
    public function log(LogRecord $record): void
    {
        $data = [
            'level' => $record->level->value,
            'message' => $record->message,
            'creation_date' => Chronos::instance($record->datetime)->toDateTimeString(),
            'creator_id' => $record->extra['creator_id'] ?? null,
            'url' => $record->extra['url'] ?? '',
            'referer' => $record->extra['referer'] ?? '',
            'request' => $record->extra['request'] ?? '',
            'ip' => $record->extra['ip'] ?? '',
            'context' => json_encode($record->context, JSON_THROW_ON_ERROR),
        ];

        $this->getEntityManager()->getConnection()->insert('log', $data);
    }

    /**
     * Returns whether the current IP often failed to log in.
     */
    public function loginFailedOften(): bool
    {
        return $this->failedOften(LogRepositoryInterface::LOGIN, LogRepositoryInterface::LOGIN_FAILED);
    }

    public function updatePasswordFailedOften(): bool
    {
        return $this->failedOften(LogRepositoryInterface::UPDATE_PASSWORD, LogRepositoryInterface::UPDATE_PASSWORD_FAILED);
    }

    public function requestPasswordResetOften(): bool
    {
        return $this->failedOften(LogRepositoryInterface::UPDATE_PASSWORD, LogRepositoryInterface::REQUEST_PASSWORD_RESET, 10);
    }

    public function registerOften(): bool
    {
        return $this->failedOften(LogRepositoryInterface::REGISTER_CONFIRM, LogRepositoryInterface::REGISTER, 10);
    }

    private function failedOften(string $success, string $failed, int $maxFailureCount = 20): bool
    {
        if (PHP_SAPI === 'cli') {
            $ip = !empty(getenv('REMOTE_ADDR')) ? getenv('REMOTE_ADDR') : 'script';
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        $select = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('message')
            ->from('log')
            ->andWhere('level = :level')
            ->setParameter('level', Level::Info->value)
            ->andWhere('message IN (:message)')
            ->setParameter('message', [$success, $failed], ArrayParameterType::STRING)
            ->andWhere('creation_date > DATE_SUB(NOW(), INTERVAL 30 MINUTE)')
            ->andWhere('ip = :ip')
            ->setParameter('ip', $ip)
            ->orderBy('id', 'DESC');

        $events = $select->executeQuery()->fetchFirstColumn();

        // Goes from present to past and count failure, until the last time we succeeded logging in
        $failureCount = 0;
        foreach ($events as $event) {
            if ($event === $success) {
                break;
            }
            ++$failureCount;
        }

        return $failureCount > $maxFailureCount;
    }

    /**
     * Delete log entries which are errors/warnings and older than two months
     * We always keep LogLevel::INFO level because we use it for statistics.
     *
     * @return int the count deleted logs
     */
    public function deleteOldLogs(): int
    {
        $connection = $this->getEntityManager()->getConnection();
        $query = $connection->createQueryBuilder()
            ->delete('log')
            ->andWhere('log.level != :level OR message IN (:message)')
            ->setParameter('level', Level::Info->value)
            ->setParameter('message', [
                LogRepositoryInterface::LOGIN,
                LogRepositoryInterface::LOGIN_FAILED,
                LogRepositoryInterface::UPDATE_PASSWORD,
                LogRepositoryInterface::REQUEST_PASSWORD_RESET,
                LogRepositoryInterface::UPDATE_PASSWORD_FAILED,
                LogRepositoryInterface::REGISTER,
                LogRepositoryInterface::REGISTER_CONFIRM,
            ], ArrayParameterType::STRING)
            ->andWhere('log.creation_date < DATE_SUB(NOW(), INTERVAL 2 MONTH)');

        $connection->executeStatement('LOCK TABLES `log` WRITE;');
        $count = $query->executeStatement();
        $connection->executeStatement('UNLOCK TABLES;');

        return $count;
    }
}

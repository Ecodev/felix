<?php

declare(strict_types=1);

namespace Ecodev\Felix\Repository\Traits;

use Cake\Chronos\Chronos;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Ecodev\Felix\Repository\LogRepository as LogRepositoryInterface;
use Laminas\Log\Logger;

trait LogRepository
{
    /**
     * @return EntityManager
     */
    abstract protected function getEntityManager();

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy the index for the from
     *
     * @return QueryBuilder
     */
    abstract public function createQueryBuilder($alias, $indexBy = null);

    /**
     * This should NOT be called directly, instead use `_log()` to log stuff.
     */
    public function log(array $event): void
    {
        $event['creation_date'] = Chronos::instance($event['timestamp'])->toDateTimeString();
        $event['extra'] = json_encode($event['extra'], JSON_THROW_ON_ERROR);
        unset($event['timestamp'], $event['priorityName'], $event['login']);

        $this->getEntityManager()->getConnection()->insert('log', $event);
    }

    /**
     * Returns whether the current IP often failed to login.
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
            ->andWhere('priority = :priority')
            ->setParameter('priority', Logger::INFO)
            ->andWhere('message IN (:message)')
            ->setParameter('message', [$success, $failed], Connection::PARAM_STR_ARRAY)
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
     * We always keep Logger::INFO level because we use it for statistics.
     *
     * @return int the count deleted logs
     */
    public function deleteOldLogs(): int
    {
        $connection = $this->getEntityManager()->getConnection();
        $query = $connection->createQueryBuilder()
            ->delete('log')
            ->andWhere('log.priority != :priority OR message IN (:message)')
            ->setParameter('priority', Logger::INFO)
            ->setParameter('message', [
                LogRepositoryInterface::LOGIN,
                LogRepositoryInterface::LOGIN_FAILED,
                LogRepositoryInterface::UPDATE_PASSWORD,
                LogRepositoryInterface::REQUEST_PASSWORD_RESET,
                LogRepositoryInterface::UPDATE_PASSWORD_FAILED,
                LogRepositoryInterface::REGISTER,
                LogRepositoryInterface::REGISTER_CONFIRM,
            ], Connection::PARAM_STR_ARRAY)
            ->andWhere('log.creation_date < DATE_SUB(NOW(), INTERVAL 2 MONTH)');

        $connection->executeStatement('LOCK TABLES `log` WRITE;');
        $count = $query->executeStatement();
        $connection->executeStatement('UNLOCK TABLES;');

        return $count;
    }
}

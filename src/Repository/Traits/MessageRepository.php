<?php

declare(strict_types=1);

namespace Ecodev\Felix\Repository\Traits;

use Doctrine\ORM\QueryBuilder;
use Ecodev\Felix\Model\Message;

trait MessageRepository
{
    /**
     * Creates a new QueryBuilder instance that is pre-populated for this entity name.
     *
     * @param string $alias
     * @param null|string $indexBy the index for the from
     *
     * @return QueryBuilder
     */
    abstract public function createQueryBuilder($alias, $indexBy = null);

    /**
     * @return Message[]
     */
    public function getAllMessageToSend(): array
    {
        $qb = $this->createQueryBuilder('message')
            ->where('message.dateSent IS NULL')
            ->addOrderBy('message.id');

        return $qb->getQuery()->setMaxResults(500)->getResult();
    }
}

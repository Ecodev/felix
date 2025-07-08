<?php

declare(strict_types=1);

namespace Ecodev\Felix\Repository\Traits;

use Doctrine\ORM\QueryBuilder;
use Ecodev\Felix\Model\Message;

trait MessageRepository
{
    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     */
    abstract public function createQueryBuilder(string $alias, ?string $indexBy = null): QueryBuilder;

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

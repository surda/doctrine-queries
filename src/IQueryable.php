<?php declare(strict_types=1);

namespace Surda\Doctrine\Queries;

use Doctrine\ORM;

interface IQueryable
{
    /**
     * Create a new QueryBuilder instance that is prepopulated for this entity name
     *
     * @param string      $alias
     * @param string|NULL $indexBy
     * @return ORM\QueryBuilder
     */
    function createQueryBuilder($alias, $indexBy = NULL);

    /**
     * @param string|NULL $dql
     * @return ORM\Query
     */
    function createQuery(?string $dql = NULL): ORM\Query;
}
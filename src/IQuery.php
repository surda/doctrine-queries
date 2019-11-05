<?php declare(strict_types=1);

namespace Surda\Doctrine\Queries;

interface IQuery
{
    /**
     * @param IQueryable $repository
     * @return ResultSet
     */
    public function fetch(IQueryable $repository): ResultSet;
}
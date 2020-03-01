<?php declare(strict_types=1);

namespace Surda\Doctrine\Queries;

use Doctrine\ORM;
use Exception;

class QueryableRepository extends ORM\EntityRepository implements IQueryable
{
    /**
     * @param string|NULL $dql
     * @return ORM\Query
     */
    function createQuery(?string $dql = NULL): ORM\Query
    {
        $dql = implode(' ', func_get_args());

        return $this->getEntityManager()->createQuery($dql);
    }

    /**
     * @param QueryObject $queryObject
     * @return ResultSet
     * @throws QueryException
     */
    public function fetch(QueryObject $queryObject): ResultSet
    {
        try {
            return $queryObject->fetch($this);
        }
        catch (Exception $e) {
            throw $this->handleQueryException($e, $queryObject);
        }
    }

    /**
     * @param Exception $e
     * @param IQuery    $queryObject
     * @return QueryException
     */
    private function handleQueryException(Exception $e, IQuery $queryObject): QueryException
    {
        $lastQuery = $queryObject instanceof QueryObject ? $queryObject->getLastQuery() : NULL;

        return new QueryException($e, $lastQuery, '[' . get_class($queryObject) . '] ' . $e->getMessage());
    }
}
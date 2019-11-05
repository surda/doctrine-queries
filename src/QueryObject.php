<?php declare(strict_types=1);

namespace Surda\Doctrine\Queries;

use Doctrine\ORM;
use Nette\SmartObject;
use Surda\Doctrine\Queries\Exception\UnexpectedValueException;

/**
 * @method onPostFetch(QueryObject $param, IQueryable $repository, \Iterator $iterator)
 */
abstract class QueryObject implements IQuery
{
    use SmartObject;

    /** @var callable[] */
    public $onPostFetch = [];

    /** @var ORM\Query|null */
    private $lastQuery;

    /** @var ResultSet */
    private $lastResult;

    /**
     * @param IQueryable $repository
     * @return ORM\QueryBuilder
     */
    protected abstract function doCreateQuery(IQueryable $repository): ORM\QueryBuilder;

    /**
     * @param IQueryable $repository
     * @return ORM\QueryBuilder
     */
    protected abstract function doCreateCountQuery(IQueryable $repository): ORM\QueryBuilder;

    /**
     * @param IQueryable $repository
     * @return ResultSet
     */
    public function fetch(IQueryable $repository): ResultSet
    {
        $this->getQuery($repository)
            ->setFirstResult(0)
            ->setMaxResults(NULL);

        return $this->lastResult;
    }

    /**
     * @param IQueryable $repository
     * @return ORM\Query
     * @throws UnexpectedValueException
     */
    private function getQuery(IQueryable $repository): ORM\Query
    {
        $query = $this->toQuery($this->doCreateQuery($repository));

        if ($this->lastQuery !== NULL && $this->lastQuery->getDQL() === $query->getDQL()) {
            $query = $this->lastQuery;
        }

        if ($this->lastQuery !== $query) {
            $this->lastResult = new ResultSet($query, $this, $repository);
        }

        return $this->lastQuery = $query;
    }

    /**
     * @param ORM\Query|ORM\QueryBuilder $query
     * @return ORM\Query
     */
    private function toQuery($query): ORM\Query
    {
        if ($query instanceof ORM\QueryBuilder) {
            return $query->getQuery();
        }

        return $query;
    }

    /**
     * @return ORM\Query|null
     * @internal For Debugging purposes only!
     */
    public function getLastQuery(): ?ORM\Query
    {
        return $this->lastQuery;
    }

    /**
     * @param IQueryable $repository
     * @param \Iterator  $iterator
     * @return void
     */
    public function postFetch(IQueryable $repository, \Iterator $iterator): void
    {
        $this->onPostFetch($this, $repository, $iterator);
    }
}
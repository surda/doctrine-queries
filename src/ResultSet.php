<?php declare(strict_types=1);

namespace Surda\Doctrine\Queries;

use ArrayIterator;
use Countable;
use Doctrine\ORM;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use IteratorAggregate;
use Nette\SmartObject;
use Nette\Utils\Paginator as NettePaginator;
use Surda\Doctrine\Queries\Exception\QueryException;

class ResultSet implements Countable, IteratorAggregate
{
    use SmartObject;

    /** @var int|NULL */
    private $totalCount;

    /** @var ORM\Query */
    private $query;

    /** @var QueryObject|NULL */
    private $queryObject;

    /** @var IQueryable|NULL */
    private $repository;

    /** @var bool|NULL */
    private $useOutputWalkers;

    /** @var ArrayIterator|NULL */
    private $iterator;

    /** @var bool */
    private $frozen = FALSE;

    /**
     * @param ORM\Query        $query
     * @param QueryObject|null $queryObject
     * @param IQueryable|null  $repository
     */
    public function __construct(ORM\Query $query, QueryObject $queryObject = NULL, IQueryable $repository = NULL)
    {
        $this->query = $query;
        $this->queryObject = $queryObject;
        $this->repository = $repository;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return ResultSet
     */
    private function applyPaging(int $offset, int $limit): ResultSet
    {
        if ($this->query->getFirstResult() != $offset || $this->query->getMaxResults() != $limit) {
            $this->query->setFirstResult($offset);
            $this->query->setMaxResults($limit);
            $this->iterator = NULL;
        }

        return $this;
    }

    /**
     * @param NettePaginator $paginator
     * @param int|null       $itemsPerPage
     * @return ResultSet
     */
    public function applyPaginator(NettePaginator $paginator, ?int $itemsPerPage = NULL): ResultSet
    {
        if ($itemsPerPage !== NULL) {
            $paginator->setItemsPerPage($itemsPerPage);
        }

        $paginator->setItemCount($this->getTotalCount());
        $this->applyPaging($paginator->getOffset(), $paginator->getLength());

        return $this;
    }


    /**
     * @return int
     * @throws QueryException
     */
    public function getTotalCount(): int
    {
        if ($this->totalCount === NULL) {
            $this->frozen = TRUE;
            $paginatedQuery = $this->createPaginatedQuery($this->query);
            $this->totalCount = $paginatedQuery->count();
        }

        return $this->totalCount;
    }

    /**
     * @return ArrayIterator
     * @throws QueryException
     */
    public function getIterator(): ArrayIterator
    {
        if ($this->iterator !== NULL) {
            return $this->iterator;
        }

        $this->frozen = TRUE;

        if ($this->query->getMaxResults() > 0 || $this->query->getFirstResult() > 0) {
            $this->iterator = $this->createPaginatedQuery($this->query)->getIterator();
        } else {
            $this->iterator = new ArrayIterator($this->query->getResult());
        }

        if ($this->queryObject !== NULL && $this->repository !== NULL) {
            $this->queryObject->postFetch($this->repository, $this->iterator);
        }

        return $this->iterator;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return iterator_to_array(clone $this->getIterator(), TRUE);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->getIterator()->count();
    }

    /**
     * @param ORM\Query $query
     * @return DoctrinePaginator
     */
    private function createPaginatedQuery(ORM\Query $query): DoctrinePaginator
    {
        $paginated = new DoctrinePaginator($query);
        $paginated->setUseOutputWalkers($this->useOutputWalkers);

        return $paginated;
    }
}
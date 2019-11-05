# Doctrine queries

-----

[![Build Status](https://travis-ci.org/surda/doctrine-queries.svg?branch=master)](https://travis-ci.org/surda/doctrine-queries)
[![Licence](https://img.shields.io/packagist/l/surda/doctrine-queries.svg?style=flat-square)](https://packagist.org/packages/surda/doctrine-queries)
[![Latest stable](https://img.shields.io/packagist/v/surda/doctrine-queries.svg?style=flat-square)](https://packagist.org/packages/surda/doctrine-queries)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

This repository is inspired by [Kdyby/Doctrine](https://github.com/Kdyby/Doctrine).

## Installation

The recommended way to is via Composer:

```
composer require surda/doctrine-queries
```
## Usage

### QueryObject

```php
use Doctrine\ORM;
use Surda\Doctrine\Queries\IQueryable;
use Surda\Doctrine\Queries\QueryObject;

class QuestionsQuery extends QueryObject
{
    /** @var array|\Closure[] */
    private $filter = [];

    /** @var array|\Closure[] */
    private $select = [];

    public function inCategory(Category $category = NULL)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($category) {
            $qb->andWhere('q.category = :category')->setParameter('category', $category->getId());
        };
        return $this;
    }
    
    public function byUser($user)
    {
        if ($user instanceof Identity) {
            $user = $user->getUser();

        } elseif (!$user instanceof User) {
            throw new InvalidArgumentException;
        }

        $this->filter[] = function (QueryBuilder $qb) use ($user) {
            $qb->andWhere('u.id = :user')->setParameter('user', $user->getId());
        };
        return $this;
    }

    public function withLastPost()
    {
        $this->select[] = function (ORM\QueryBuilder $qb) {
            $qb->addSelect('partial lp.{id, createdAt}, partial lpa.{id}, partial lpau.{id, name}')
                ->leftJoin('q.lastPost', 'lp', Join::WITH, 'lp.spam = FALSE AND lp.deleted = FALSE')
                ->leftJoin('lp.author', 'lpa')
                ->leftJoin('lpa.user', 'lpau');
        };
        return $this;
    }

    public function withCategory()
    {
        $this->select[] = function (ORM\QueryBuilder $qb) {
            $qb->addSelect('c, pc')
                ->innerJoin('q.category', 'c')
                ->innerJoin('c.parent', 'pc');
        };
        return $this;
    }

    public function withAnswersCount()
    {
        $this->select[] = function (ORM\QueryBuilder $qb) {
            $subCount = $qb->getEntityManager()->createQueryBuilder()
                ->select('COUNT(a.id)')->from(Answer::class, 'a')
                ->andWhere('a.spam = FALSE AND a.deleted = FALSE')
                ->andWhere('a.question = q');

            $qb->addSelect("($subCount) AS answers_count");
        };
        return $this;
    }

    public function sortByPinned($order = 'ASC')
    {
        $this->select[] = function (ORM\QueryBuilder $qb) use ($order) {
            $qb->addSelect('FIELD(q.pinned, TRUE, FALSE) as HIDDEN isPinned');
            $qb->addOrderBy('isPinned', $order);
        };
        return $this;
    }

    public function sortByHasSolution($order = 'ASC')
    {
        $this->select[] = function (ORM\QueryBuilder $qb) use ($order) {
            $qb->addSelect('FIELD(IsNull(q.solution), TRUE, FALSE) as HIDDEN hasSolution');
            $qb->addOrderBy('hasSolution', $order);
        };
        return $this;
    }

    /**
     * @param IQueryable $repository
     * @return ORM\QueryBuilder
     */
    protected function doCreateQuery(IQueryable $repository)
    {
        $qb = $this->createBasicDql($repository)
            ->addSelect('partial i.{id}, partial u.{id, name}');

        foreach ($this->select as $modifier) {
            $modifier($qb);
        }

        return $qb->addOrderBy('q.createdAt', 'DESC');
    }

    /**
     * @param IQueryable $repository
     * @return ORM\QueryBuilder
     */
    protected function doCreateCountQuery(IQueryable $repository)
    {
        return $this->createBasicDql($repository)->select('COUNT(q.id)');
    }

    /**
     * @param IQueryable $repository
     * @return ORM\QueryBuilder
     */
    private function createBasicDql(IQueryable $repository)
    {
        $qb = $repository->createQueryBuilder()
            ->select('q')->from(Question::class, 'q')
            ->andWhere('q.spam = FALSE AND q.deleted = FALSE')
            ->innerJoin('q.author', 'i')
            ->innerJoin('i.user', 'u');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }
}
```

```php
$query = (new QuestionsQuery())
	->withLastPost()
	->byUser($user);

$result = $repository->fetch($query);
```

### ResultSet

```php
class Repository extends \Surda\Doctrine\Queries\EntityRepository
{
    /**
     * @return \Surda\Doctrine\Queries\ResultSet
     */
    public function getResultSet(): \Surda\Doctrine\Queries\ResultSet
    {
        $query = $this->createQueryBuilder('q')->addOrderBy('q.id', 'DESC')->getQuery();

        return new \Surda\Doctrine\Queries\ResultSet($query);
    }
}
```

```php
$visualPaginator = $this['vp'];
$paginator = $visualPaginator->getPaginator();
$paginator->setItemsPerPage(20);

$resultSet = $this->repository->getResultSet();
$resultSet->applyPaginator($paginator);

foreach ($resultSet as $entity) {
	// ...
}
```
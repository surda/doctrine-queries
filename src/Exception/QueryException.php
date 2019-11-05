<?php declare(strict_types=1);

namespace Surda\Doctrine\Queries\Exception;

use Doctrine\ORM;
use RuntimeException;
use Throwable;

class QueryException extends RuntimeException
{
    /** @var ORM\AbstractQuery|NULL */
    public $query;

    /**
     * @param Throwable          $previous
     * @param ORM\AbstractQuery|NULL $query
     * @param string|NULL        $message
     */
    public function __construct(Throwable $previous, ?ORM\AbstractQuery $query = NULL, ?string $message = NULL)
    {
        parent::__construct($message ?: $previous->getMessage(), 0, $previous);
        $this->query = $query;
    }
}
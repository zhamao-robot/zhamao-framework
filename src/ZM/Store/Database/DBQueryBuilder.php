<?php

declare(strict_types=1);

namespace ZM\Store\Database;

use Doctrine\DBAL\Query\QueryBuilder;
use ZM\Store\Database\DBException as DbException;

class DBQueryBuilder extends QueryBuilder
{
    private DBWrapper $wrapper;

    public function __construct(DBWrapper $wrapper)
    {
        parent::__construct($wrapper->getConnection());
        $this->wrapper = $wrapper;
    }

    /**
     * @throws DbException
     */
    public function execute(): DBStatementWrapper|int
    {
        if ($this->getType() === self::SELECT) {
            return $this->wrapper->executeQuery($this->getSQL(), $this->getParameters(), $this->getParameterTypes());
        }
        return $this->wrapper->executeStatement($this->getSQL(), $this->getParameters(), $this->getParameterTypes());
    }
}

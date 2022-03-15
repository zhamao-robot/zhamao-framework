<?php

declare(strict_types=1);

namespace ZM\MySQL;

use Doctrine\DBAL\Query\QueryBuilder;
use ZM\Exception\DbException;

class MySQLQueryBuilder extends QueryBuilder
{
    private $wrapper;

    public function __construct(MySQLWrapper $wrapper)
    {
        parent::__construct($wrapper->getConnection());
        $this->wrapper = $wrapper;
    }

    /**
     * @throws DbException
     * @return int|MySQLStatementWrapper
     */
    public function execute()
    {
        if ($this->getType() === self::SELECT) {
            return $this->wrapper->executeQuery($this->getSQL(), $this->getParameters(), $this->getParameterTypes());
        }
        return $this->wrapper->executeStatement($this->getSQL(), $this->getParameters(), $this->getParameterTypes());
    }
}

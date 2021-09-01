<?php


namespace ZM\MySQL;


use Doctrine\DBAL\Query\QueryBuilder;
use ZM\Exception\DbException;

class MySQLQueryBuilder extends QueryBuilder
{
    private $wrapper;

    public function __construct(MySQLWrapper $wrapper) {
        parent::__construct($wrapper->getConnection());
        $this->wrapper = $wrapper;
    }

    /**
     * @return int|MySQLStatementWrapper
     * @throws DbException
     */
    public function execute() {
        if ($this->getType() === self::SELECT) {
            return $this->wrapper->executeQuery($this->getSQL(), $this->getParameters(), $this->getParameterTypes());
        }
        return $this->wrapper->executeStatement($this->getSQL(), $this->getParameters(), $this->getParameterTypes());
    }
}
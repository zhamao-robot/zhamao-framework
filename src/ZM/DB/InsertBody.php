<?php

declare(strict_types=1);

namespace ZM\DB;

use ZM\Exception\DbException;

/**
 * Class InsertBody
 * @deprecated This will delete in 2.6 or future version, use \ZM\MySQL\MySQLManager::getConnection() instead
 */
class InsertBody
{
    /**
     * @var Table
     */
    private $table;

    private $row;

    /**
     * InsertBody constructor.
     * @param $row
     */
    public function __construct(Table $table, $row)
    {
        $this->table = $table;
        $this->row = $row;
    }

    /**
     * @throws DbException
     */
    public function save()
    {
        DB::rawQuery('INSERT INTO ' . $this->table->getTableName() . ' VALUES (' . implode(',', array_fill(0, count($this->row), '?')) . ')', $this->row);
    }
}

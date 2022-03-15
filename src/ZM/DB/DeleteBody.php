<?php

declare(strict_types=1);

namespace ZM\DB;

use ZM\Exception\DbException;

/**
 * Class DeleteBody
 * @deprecated This will delete in 2.6 or future version, use \ZM\MySQL\MySQLManager::getConnection() instead
 */
class DeleteBody
{
    use WhereBody;

    /**
     * @var Table
     */
    private $table;

    /**
     * DeleteBody constructor.
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * @throws DbException
     * @return mixed
     */
    public function save()
    {
        [$sql, $param] = $this->getWhereSQL();
        return DB::rawQuery('DELETE FROM ' . $this->table->getTableName() . ' WHERE ' . $sql, $param);
    }
}

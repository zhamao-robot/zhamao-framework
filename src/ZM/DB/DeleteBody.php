<?php


namespace ZM\DB;


use ZM\Exception\DbException;

/**
 * Class DeleteBody
 * @package ZM\DB
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
     * @param Table $table
     */
    public function __construct(Table $table) {
        $this->table = $table;
    }

    /**
     * @return mixed
     * @throws DbException
     */
    public function save() {
        list($sql, $param) = $this->getWhereSQL();
        return DB::rawQuery("DELETE FROM " . $this->table->getTableName() . " WHERE " . $sql, $param);
    }
}

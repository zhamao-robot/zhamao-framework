<?php


namespace ZM\DB;


use ZM\Exception\DbException;

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

<?php


namespace ZM\DB;


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

    public function save() {
        list($sql, $param) = $this->getWhereSQL();
        return DB::rawQuery("DELETE FROM " . $this->table->getTableName() . " WHERE " . $sql, $param);
    }
}
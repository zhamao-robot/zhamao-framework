<?php


namespace ZM\DB;


class InsertBody
{
    /**
     * @var Table
     */
    private $table;
    private $row;

    /**
     * InsertBody constructor.
     * @param Table $table
     * @param $row
     */
    public function __construct(Table $table, $row) {
        $this->table = $table;
        $this->row = $row;
    }

    public function save() {
        DB::rawQuery('INSERT INTO ' . $this->table->getTableName() . ' VALUES ('.implode(',', array_fill(0, 5, '?')).')', $this->row);
    }
}
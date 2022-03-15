<?php

declare(strict_types=1);

namespace ZM\DB;

use ZM\Exception\DbException;

/**
 * Class UpdateBody
 * @deprecated This will delete in 2.6 or future version, use \ZM\MySQL\MySQLManager::getConnection() instead
 */
class UpdateBody
{
    use WhereBody;

    /**
     * @var Table
     */
    private $table;

    /**
     * @var array
     */
    private $set_value;

    /**
     * UpdateBody constructor.
     */
    public function __construct(Table $table, array $set_value)
    {
        $this->table = $table;
        $this->set_value = $set_value;
    }

    /**
     * @throws DbException
     */
    public function save()
    {
        $arr = [];
        $msg = [];
        foreach ($this->set_value as $k => $v) {
            $msg[] = $k . ' = ?';
            $arr[] = $v;
        }
        if (($msg ?? []) == []) {
            throw new DbException('update value sets can not be empty!');
        }
        $line = 'UPDATE ' . $this->table->getTableName() . ' SET ' . implode(', ', $msg);
        if ($this->where_thing != []) {
            [$sql, $param] = $this->getWhereSQL();
            $arr = array_merge($arr, $param);
            $line .= ' WHERE ' . $sql;
        }
        return DB::rawQuery($line, $arr);
    }
}

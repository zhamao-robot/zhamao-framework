<?php

/**
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace ZM\DB;

/**
 * Class Table
 * @deprecated This will delete in 2.6 or future version, use \ZM\MySQL\MySQLManager::getConnection() instead
 */
class Table
{
    /** @var SelectBody[] */
    public $cache = [];

    private $table_name;

    private static $table_instance = [];

    public function __construct($table_name)
    {
        $this->table_name = $table_name;
        self::$table_instance[$table_name] = $this;
    }

    public static function getTableInstance($table_name)
    {
        if (isset(self::$table_instance[$table_name])) {
            return self::$table_instance[$table_name];
        }
        return null;
    }

    public function select($what = [])
    {
        return new SelectBody($this, $what == [] ? ['*'] : $what);
    }

    public function where($column, $operation_or_value, $value = null)
    {
        return (new SelectBody($this, ['*']))->where($column, $operation_or_value, $value);
    }

    public function insert($row)
    {
        $this->cache = [];
        return new InsertBody($this, $row);
    }

    public function update(array $set_value)
    {
        $this->cache = [];
        return new UpdateBody($this, $set_value);
    }

    public function delete()
    {
        $this->cache = [];
        return new DeleteBody($this);
    }

    public function statement()
    {
        $this->cache = [];
        //TODO: 无返回的statement语句
    }

    public function paintWhereSQL($rule, $operator)
    {
        if ($rule == []) {
            return ['', []];
        }
        $msg = '';
        $param = [];
        foreach ($rule as $k => $v) {
            if ($msg == '') {
                $msg .= $k . " {$operator} ? ";
            } else {
                $msg .= ' AND ' . $k . " {$operator} ?";
            }
            $param[] = $v;
        }
        return [$msg, $param];
    }

    /**
     * @return mixed
     */
    public function getTableName()
    {
        return $this->table_name;
    }
}

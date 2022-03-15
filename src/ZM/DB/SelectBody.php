<?php

declare(strict_types=1);

namespace ZM\DB;

use ZM\Exception\DbException;

/**
 * Class SelectBody
 * @deprecated This will delete in 2.6 or future version, use \ZM\MySQL\MySQLManager::getConnection() instead
 */
class SelectBody
{
    use WhereBody;

    /** @var Table */
    private $table;

    private $select_thing;

    private $result;

    public function __construct($table, $select_thing)
    {
        $this->table = $table;
        $this->select_thing = $select_thing;
    }

    /**
     * @throws DbException
     */
    public function get()
    {
        return $this->fetchAll();
    }

    /**
     * @throws DbException
     */
    public function count(): int
    {
        $this->select_thing = ['count(*)'];
        $str = $this->queryPrepare();
        $this->result = DB::rawQuery($str[0], $str[1]);
        return intval($this->result[0]['count(*)']);
    }

    /**
     * @param  int         $fetch_mode
     * @throws DbException
     */
    public function fetchAll($fetch_mode = ZM_DEFAULT_FETCH_MODE)
    {
        $this->execute($fetch_mode);
        return $this->getResult();
    }

    /**
     * @throws DbException
     * @return null|mixed
     */
    public function fetchFirst()
    {
        return $this->fetchAll()[0] ?? null;
    }

    /**
     * @param  null        $key
     * @throws DbException
     * @return null|mixed
     */
    public function value($key = null)
    {
        $r = $this->fetchFirst();
        if ($r === null) {
            return null;
        }
        if ($key === null) {
            return current($r);
        }
        return $r[$key] ?? null;
    }

    /**
     * @param  int         $fetch_mode
     * @throws DbException
     */
    public function execute($fetch_mode = ZM_DEFAULT_FETCH_MODE)
    {
        $str = $this->queryPrepare();
        $this->result = DB::rawQuery($str[0], $str[1], $fetch_mode);
    }

    public function getResult()
    {
        return $this->result;
    }

    public function equals(SelectBody $body): bool
    {
        if ($this->select_thing != $body->getSelectThing()) {
            return false;
        }
        if ($this->where_thing == $body->getWhereThing()) {
            return false;
        }
        return true;
    }

    /**
     * @return mixed
     */
    public function getSelectThing()
    {
        return $this->select_thing;
    }

    public function getWhereThing(): array
    {
        return $this->where_thing;
    }

    private function queryPrepare(): array
    {
        $msg = 'SELECT ' . implode(', ', $this->select_thing) . ' FROM ' . $this->table->getTableName();
        $sql = $this->table->paintWhereSQL($this->where_thing['='] ?? [], '=');
        if ($sql[0] != '') {
            $msg .= ' WHERE ' . $sql[0];
            $array = $sql[1];
            $sql = $this->table->paintWhereSQL($this->where_thing['!='] ?? [], '!=');
            if ($sql[0] != '') {
                $msg .= ' AND ' . $sql[0];
            }
            $array = array_merge($array, $sql[1]);
        }
        return [$msg, $array ?? []];
    }
}

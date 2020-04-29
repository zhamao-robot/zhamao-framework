<?php


namespace ZM\DB;


use Framework\Console;
use ZM\Exception\DbException;

class SelectBody
{
    use WhereBody;

    /** @var Table */
    private $table;

    private $select_thing;


    private $result = null;

    public function __construct($table, $select_thing) {
        $this->table = $table;
        $this->select_thing = $select_thing;
    }

    /**
     * @return null
     * @throws DbException
     */
    public function get() { return $this->fetchAll(); }

    /**
     * @return null
     * @throws DbException
     */
    public function fetchAll() {
        if ($this->table->isCacheEnabled()) {
            $rr = md5(implode(",", $this->select_thing) . serialize($this->where_thing));
            if (array_key_exists($rr, $this->table->cache)) {
                Console::info('SQL query cached: ' . $rr, date("[H:i:s ") . 'DB] ');
                return $this->table->cache[$rr]->getResult();
            }
        }
        $this->execute();
        if ($this->table->isCacheEnabled() && !in_array($rr, $this->table->cache)) {
            $this->table->cache[$rr] = $this;
        }
        return $this->getResult();
    }

    /**
     * @return mixed|null
     * @throws DbException
     */
    public function fetchFirst() {
        return $this->fetchAll()[0] ?? null;
    }

    /**
     * @param null $key
     * @return mixed|null
     * @throws DbException
     */
    public function value($key = null) {
        $r = $this->fetchFirst();
        if ($r === null) return null;
        if ($key === null)
            return current($r);
        else return $r[$key] ?? null;
    }

    /**
     * @throws DbException
     */
    public function execute() {
        $str = $this->queryPrepare();
        $this->result = DB::rawQuery($str[0], $str[1]);
    }

    public function getResult() { return $this->result; }

    public function equals(SelectBody $body) {
        if ($this->select_thing != $body->getSelectThing()) return false;
        elseif ($this->where_thing == $body->getWhereThing()) return false;
        else return true;
    }

    /**
     * @return mixed
     */
    public function getSelectThing() { return $this->select_thing; }

    /**
     * @return array
     */
    public function getWhereThing() { return $this->where_thing; }

    private function queryPrepare() {
        $msg = "SELECT " . implode(", ", $this->select_thing) . " FROM " . $this->table->getTableName();
        $sql = $this->table->paintWhereSQL($this->where_thing['='] ?? [], '=');
        if ($sql[0] != '') {
            $msg .= " WHERE " . $sql[0];
            $array = $sql[1];
            $sql = $this->table->paintWhereSQL($this->where_thing['!='] ?? [], '!=');
            if ($sql[0] != '') $msg .= " AND " . $sql[0];
            $array = array_merge($array, $sql[1]);
        }
        return [$msg, $array ?? []];
    }
}

<?php


namespace ZM\DB;


use ZM\Console\Console;
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
     * @throws DbException
     */
    public function count() {
        $this->select_thing = ["count(*)"];
        $str = $this->queryPrepare();
        $this->result = DB::rawQuery($str[0], $str[1]);
        return intval($this->result[0]["count(*)"]);
    }

    /**
     * @param int $fetch_mode
     * @return null
     * @throws DbException
     */
    public function fetchAll($fetch_mode = ZM_DEFAULT_FETCH_MODE) {
        if ($this->table->isCacheEnabled()) {
            $rr = md5(implode(",", $this->select_thing) . serialize($this->where_thing));
            if (array_key_exists($rr, $this->table->cache)) {
                Console::debug('SQL query cached: ' . $rr);
                return $this->table->cache[$rr]->getResult();
            }
        }
        $this->execute($fetch_mode);
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
     * @param int $fetch_mode
     * @throws DbException
     */
    public function execute($fetch_mode = ZM_DEFAULT_FETCH_MODE) {
        $str = $this->queryPrepare();
        $this->result = DB::rawQuery($str[0], $str[1], $fetch_mode);
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

<?php


namespace ZM\DB;


trait WhereBody
{
    protected $where_thing = [];

    public function where($column, $operation_or_value, $value = null) {
        if ($value !== null) $this->where_thing[$operation_or_value][$column] = $value;
        elseif (!in_array($operation_or_value, ['=', '!=', '>', '<', '>=', '<=', 'IN', 'in'])) $this->where_thing['='][$column] = $operation_or_value;
        else $this->where_thing['='][$column] = $operation_or_value;
        return $this;
    }

    protected function getWhereSQL(){
        $param = [];
        $msg = '';
        foreach($this->where_thing as $k => $v) {
            foreach($v as $ks => $vs) {
                if($param != []) {
                    $msg .= ' AND '.$ks ." $k ?";
                } else {
                    $msg .= "$ks $k ?";
                }
                $param []=$vs;
            }
        }
        if ($msg == '') $msg = 1;
        return [$msg, $param];
    }
}

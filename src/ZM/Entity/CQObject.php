<?php


namespace ZM\Entity;


class CQObject
{
    public $type;
    public $params;
    public $start;
    public $end;

    public function __construct($type = "", $params = [], $start = 0, $end = 0) {
        if ($type !== "") {
            $this->type = $type;
            $this->params = $params;
            $this->start = $start;
            $this->end = $end;
        }
    }

    public static function fromArray($arr) {
        return new CQObject($arr["type"], $arr["params"] ?? [], $arr["start"], $arr["end"]);
    }
}
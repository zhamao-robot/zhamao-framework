<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/6/3
 * Time: 下午3:12
 */

class Example extends ModBase
{
    protected $cmds;

    public function __construct(CQBot $main, $data){
        parent::__construct($main, $data);
    }

    public function execute($it){
        switch ($it[0]) {
            case "ping":
                $this->reply("pong");
                return true;
            case "test":
                $this->reply("Hello world");
                return true;
        }
        return false;
    }
}
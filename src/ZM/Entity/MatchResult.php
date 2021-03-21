<?php


namespace ZM\Entity;


use ZM\Annotation\CQ\CQCommand;

class MatchResult
{
    /** @var bool */
    public $status = false;
    /** @var CQCommand|null */
    public $object = null;
    /** @var array */
    public $match = [];
}
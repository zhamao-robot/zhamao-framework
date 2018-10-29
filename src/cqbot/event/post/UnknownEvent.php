<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/10/15
 * Time: 10:40 AM
 */

class UnknownEvent extends Event
{
    public function __construct($req) {
        Console::info("Unknown event.");
    }
}
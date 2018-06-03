<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午3:10
 */

abstract class Event
{
    /**
     * @return Framework
     */
    public function getFramework(){
        return Framework::getInstance();
    }
}
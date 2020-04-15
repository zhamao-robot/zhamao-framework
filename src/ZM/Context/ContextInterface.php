<?php


namespace ZM\Context;


interface ContextInterface
{
    public function __construct($param, $cid);

    public function getServer();

    public function getFrame();

    public function getData();

    public function getCid();

    public function getResponse();

    public function getRequest();
}

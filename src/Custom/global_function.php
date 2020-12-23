<?php

//这里写你的全局函数
function pgo(callable $func, $name = "default") {
    \ZM\Utils\CoroutinePool::go($func, $name);
}

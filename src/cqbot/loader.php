<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/12
 * Time: 10:32
 */

function loadAllClass($dir){
    $dir_obj = scandir($dir);
    unset($dir_obj[0], $dir_obj[1]);
    foreach ($dir_obj as $m) {
        $taskFileName = explode(".", $m);
        if (is_dir($dir . $m . "/")) loadAllClass($dir . $m . "/");
        else {
            if (count($taskFileName) < 2 || $taskFileName[1] != "php") continue;
            require_once($dir . $m);
            Console::debug("Loading PHP file ".$dir.$m);
        }
    }
}

//加载需要优先加载的文件
require_once(WORKING_DIR."src/cqbot/mods/ModBase.php");

loadAllClass(WORKING_DIR."src/cqbot/");

//加载外部模块
require_once(WORKING_DIR."src/extension/PHPMailer.phar");

/**
 * 下面是不能在loader里面加载的php文件，以下文件更新时必须停止脚本后再重启才能重新加载
 * start.php
 * src/cqbot/Framework.php
 * src/cqbot/Console.php
 * src/cqbot/utils/Buffer.php
 * src/cqbot/ErrorStatus.php
 */
<?php

function loader__generated_id__() {
    $obj = json_decode(file_get_contents(__DIR__.'/zmplugin.json'), true);
    foreach(($obj["autoload-psr-4"] ?? []) as $v) {
        require_once Phar::running().'/'.$v;
    }

}
return json_decode(file_get_contents(__DIR__.'/zmplugin.json'), true) ?? ['zm_module' => false];
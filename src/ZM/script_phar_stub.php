<?php

declare(strict_types=1);

function loader__generated_id__()
{
    $obj = json_decode(file_get_contents(__DIR__ . '/zmplugin.json'), true);
    foreach (($obj['hotload-psr-4'] ?? []) as $v) {
        require_once Phar::running() . '/' . $v;
    }
    foreach (($obj['hotload-files'] ?? []) as $v) {
        require_once Phar::running() . '/' . $v;
    }
}
return json_decode(file_get_contents(__DIR__ . '/zmplugin.json'), true) ?? ['zm_module' => false];

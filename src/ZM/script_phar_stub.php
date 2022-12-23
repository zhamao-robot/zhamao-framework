<?php

declare(strict_types=1);

const _PHAR_STUB_ID = '__generated_id__';

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
if (('__generate' . 'd_id__') === _PHAR_STUB_ID) {
    echo 'Cannot execute this file directly!' . PHP_EOL;
    exit(1);
}
/* @phpstan-ignore-next-line */
return json_decode(file_get_contents(__DIR__ . '/zmplugin.json'), true) ?? ['zm_module' => false];

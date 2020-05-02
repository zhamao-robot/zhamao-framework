<?php

global $is_phar;

use Framework\FrameworkLoader;

$is_phar = true;

if (substr(__DIR__, 0, 7) != 'phar://') {
    die("You can not run this script directly!\n");
}

testEnvironment();

spl_autoload_register(function ($class) {
    //echo $class."\n";
    $exp = str_replace("\\", '/', $class);
    $exp = __DIR__ . '/src/' . $exp . '.php';
    if (is_file($exp)) {
        require_once $exp;
    }
});

loadPhp(__DIR__ . '/src');

Swoole\Coroutine::set([
    'max_coroutine' => 30000,
]);

date_default_timezone_set("Asia/Shanghai");

define('WORKING_DIR', __DIR__);

$s = new FrameworkLoader($argv);

function loadPhp($dir) {
    $dirs = scandir($dir);
    foreach ($dirs as $v) {
        $path = $dir . '/' . $v;
        if (is_dir($path)) {
            loadPhp($path);
        } else {
            if (pathinfo($dir . '/' . $v)['extension'] == 'php') {
                //echo 'loading '.$path.PHP_EOL;
                require_once $path;
            }
        }
    }
}

function testEnvironment() {
    $current_dir = realpath('.');
    @mkdir($current_dir . '/config/');
    if (!is_file($current_dir . '/config/global.php')) {
        echo "Exporting default global config...\n";
        $global = file_get_contents(__DIR__ . '/config/global.php');
        $global = str_replace("WORKING_DIR", 'realpath("../")', $global);
        file_put_contents($current_dir . '/config/global.php', $global);
    }
    if (!is_file($current_dir . '/config/file_header.json')) {
        echo "Exporting default file_header config...\n";
        $global = file_get_contents(__DIR__ . '/config/file_header.json');
        file_put_contents($current_dir . '/config/file_header.json', $global);
    }
    if (!is_dir($current_dir . '/resources')) mkdir($current_dir . '/resources');
    if (!is_dir($current_dir . '/src')) mkdir($current_dir . '/src');
    if (!is_dir($current_dir . '/src')) mkdir($current_dir . '/src');
    if (!is_dir($current_dir . '/src/Module')) {
        mkdir($current_dir . '/src/Module');
        mkdir($current_dir . '/src/Module/Example');
        file_put_contents($current_dir . '/src/Module/Example/Hello.php', file_get_contents(__DIR__ . '/tmp/Hello.php.bak'));
        mkdir($current_dir . '/src/Module/Middleware');
        file_put_contents($current_dir . '/src/Module/Middleware/TimerMiddleware.php', file_get_contents(__DIR__ . '/tmp/TimerMiddleware.php.bak'));
    }
    if (!is_dir($current_dir . '/src/Custom')) {
        mkdir($current_dir . '/src/Custom');
        mkdir($current_dir . '/src/Custom/Annotation');
        mkdir($current_dir . '/src/Custom/Connection');
        file_put_contents($current_dir . '/src/Custom/global_function.php', "<?php\n\n//这里写你的全局方法");
    }
}


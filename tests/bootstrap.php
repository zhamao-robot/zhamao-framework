<?php

declare(strict_types=1);

use ZM\Logger\ConsoleLogger;

require_once __DIR__ . '/../vendor/autoload.php';

ob_logger_register(new ConsoleLogger('error'));

<?php

declare(strict_types=1);

namespace ZM\Config;

class ConfigMetadata
{
    public $is_patch = false;

    public $is_env = false;

    public $path = '';

    public $extension = '';

    public $data = [];
}

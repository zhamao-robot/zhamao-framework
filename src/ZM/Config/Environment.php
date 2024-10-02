<?php

declare(strict_types=1);

namespace ZM\Config;

class Environment implements EnvironmentInterface
{
    private const VALUE_MAP = [
        'true' => true,
        '(true)' => true,
        'false' => false,
        '(false)' => false,
        'null' => null,
        '(null)' => null,
        'empty' => '',
    ];

    private array $values;

    /**
     * @param array $values    额外的环境变量，优先级高于系统环境变量
     * @param bool  $overwrite 是否允许后续 set() 覆盖已有的环境变量
     */
    public function __construct(
        array $values = [],
        private bool $overwrite = false
    ) {
        $this->values = $values + $_ENV + $_SERVER;
    }

    public function set(string $name, mixed $value): self
    {
        if (array_key_exists($name, $this->values) && !$this->overwrite) {
            // 如不允许覆盖已有的环境变量，则不做任何操作
            return $this;
        }

        $this->values[$name] = $_ENV[$name] = $value;
        putenv("{$name}={$value}");

        return $this;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        if (isset($this->values[$name])) {
            return $this->normalize($this->values[$name]);
        }

        return $default;
    }

    public function getAll(): array
    {
        $result = [];

        foreach ($this->values as $key => $value) {
            $result[$key] = $this->normalize($value);
        }

        return $result;
    }

    protected function normalize(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return self::VALUE_MAP[strtolower($value)] ?? $value;
    }
}

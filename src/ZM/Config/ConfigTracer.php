<?php

declare(strict_types=1);

namespace ZM\Config;

class ConfigTracer
{
    /**
     * @var array 配置加载跟踪
     */
    private array $traces = [];

    /**
     * 添加配置跟踪路径
     *
     * @param string $group  配置组
     * @param array  $traces 配置项
     * @param string $source 配置源
     */
    public function addTracesOf(string $group, array $traces, string $source): void
    {
        $this->traces = array_merge($this->traces, $this->flatten($group, $traces, $source));
    }

    /**
     * 获取配置项的来源
     *
     * @param  string      $key 配置项
     * @return null|string 来源，如果没有找到，返回 null
     */
    public function getTraceOf(string $key): ?string
    {
        return $this->traces[$key] ?? null;
    }

    /**
     * 扁平化配置
     *
     * @param  string $prefix 前缀
     * @param  array  $array  数组
     * @param  string $source 来源
     * @return array  扁平化后的数组，键名为 $prefix . $key，键值为 $source
     */
    private function flatten(string $prefix, array $array, string $source): array
    {
        $result = [];
        $prefix = $prefix ? $prefix . '.' : '';
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result += $this->flatten($prefix . $key, $value, $source);
            } else {
                $result[$prefix . $key] = $source;
            }
        }
        return $result;
    }
}

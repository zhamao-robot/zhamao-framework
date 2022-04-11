# ZM\API\Proxies\Bot\AllBotsProxy

## __call

```php
public function __call(string $name, array $arguments): array<mixed>
```

### 描述

在所有机器人实例上调用方法

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| name | string | 方法名 |
| arguments | array | 参数 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array<mixed> | 返回一个包含所有执行结果的数组，键名为机器人ID |

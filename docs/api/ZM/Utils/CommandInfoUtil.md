# ZM\Utils\CommandInfoUtil

## exists

```php
public function exists(): bool
```

### 描述

判断命令信息是否已生成并缓存

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## get

```php
public function get(): array
```

### 描述

获取命令信息

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## regenerate

```php
public function regenerate(): void
```

### 描述

重新生成命令信息

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## getHelp

```php
public function getHelp(string $command_id, bool $simple): string
```

### 描述

获取命令帮助

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| command_id | string | 命令ID，为 `class@method` 格式 |
| simple | bool | 是否仅输出简易信息（只有命令触发条件和描述） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## save

```php
public function save(array $helps): void
```

### 描述

缓存命令信息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| helps | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## generateCommandList

```php
public function generateCommandList(): array
```

### 描述

根据注解树生成命令信息（内部）

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## generateCommandArgumentList

```php
public function generateCommandArgumentList(string $id): array
```

### 描述

生成指定命令的参数列表

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id | string | 命令 ID |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |

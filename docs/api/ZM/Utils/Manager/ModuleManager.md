# ZM\Utils\Manager\ModuleManager

## getConfiguredModules

```php
public function getConfiguredModules(): array
```

### 描述

扫描src目录下的所有已经被标注的模块

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## packModule

```php
public function packModule(mixed $module): bool
```

### 描述

打包模块

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| module | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## unpackModule

```php
public function unpackModule(mixed $module, array $options): array|false
```

### 描述

解包模块

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| module | mixed |  |
| options | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|false |  |

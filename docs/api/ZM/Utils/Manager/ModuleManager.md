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
public function packModule(array $module, string $target): bool
```

### 描述

打包模块

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| module | array | 模块信息 |
| target | string | 目标路径 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## unpackModule

```php
public function unpackModule(array|Iterator $module, array $options): array|false
```

### 描述

解包模块

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| module | array|Iterator | 模块信息 |
| options | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|false | 返回解包的信息或false |

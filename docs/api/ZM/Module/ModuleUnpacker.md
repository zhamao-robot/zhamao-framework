# ZM\Module\ModuleUnpacker

## unpack

```php
public function unpack(mixed $ignore_depends, bool $override_light_cache, bool $override_data_files, bool $override_source): array
```

### 描述

解包模块

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| ignore_depends | mixed |  |
| override_light_cache | bool |  |
| override_data_files | bool |  |
| override_source | bool |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## checkConfig

```php
public function checkConfig(): mixed
```

### 描述

检查模块配置文件是否正确地放在phar包的位置中

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## checkDepends

```php
public function checkDepends(mixed $ignore_depends): mixed
```

### 描述

检查模块依赖关系

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| ignore_depends | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## checkLightCacheStore

```php
public function checkLightCacheStore(): mixed
```

### 描述

检查 light-cache-store 项是否合规

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## checkZMDataStore

```php
public function checkZMDataStore(): mixed
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## copyZMDataStore

```php
public function copyZMDataStore(mixed $override_data): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| override_data | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |

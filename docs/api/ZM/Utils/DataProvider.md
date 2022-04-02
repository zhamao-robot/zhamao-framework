# ZM\Utils\DataProvider

## getResourceFolder

```php
public function getResourceFolder(): string
```

### 描述

返回资源目录

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## getWorkingDir

```php
public function getWorkingDir(): false|string
```

### 描述

返回工作目录，不带最右边文件夹的斜杠（/）

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| false|string |  |


## getFrameworkRootDir

```php
public function getFrameworkRootDir(): false|string
```

### 描述

获取框架所在根目录

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| false|string |  |


## getSourceRootDir

```php
public function getSourceRootDir(): false|string
```

### 描述

获取源码根目录，除Phar模式外均与工作目录相同

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| false|string |  |


## getFrameworkLink

```php
public function getFrameworkLink(): null|array|false|mixed
```

### 描述

获取框架反代链接

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|false|mixed |  |


## getDataFolder

```php
public function getDataFolder(string $second): null|array|false|mixed|string
```

### 描述

获取zm_data数据目录，如果二级目录不为空，则自动创建目录并返回

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| second | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|false|mixed|string |  |


## saveToJson

```php
public function saveToJson(string $filename, array|int|Iterator|JsonSerializable|string|Traversable $file_array): false|int
```

### 描述

将变量保存在zm_data下的数据目录，传入数组

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| filename | string | 文件名 |
| file_array | array|int|Iterator|JsonSerializable|string|Traversable | 文件内容数组 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| false|int | 返回文件大小或false |


## loadFromJson

```php
public function loadFromJson(string $filename): null|mixed
```

### 描述

从json加载变量到内存

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| filename | string | 文件名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|mixed | 返回文件内容数据或null |


## scanDirFiles

```php
public function scanDirFiles(string $dir, bool $recursive, bool|string $relative): array|false
```

### 描述

递归或非递归扫描目录，可返回相对目录的文件列表或绝对目录的文件列表

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| dir | string | 目录 |
| recursive | bool | 是否递归扫描子目录 |
| relative | bool|string | 是否返回相对目录，如果为true则返回相对目录，如果为false则返回绝对目录 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|false |  |


## isRelativePath

```php
public function isRelativePath(string $path): bool
```

### 描述

检查路径是否为相对路径（根据第一个字符是否为"/"来判断）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| path | string | 路径 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool | 返回结果 |

# ZM\DB\DB

## initTableList

```php
public function initTableList(string $db_name): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| db_name | string | 数据库名称 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## table

```php
public function table(string $table_name): Table
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| table_name | string | 表名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Table | 返回表对象 |


## statement

```php
public function statement(string $line): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| line | string | SQL语句 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## unprepared

```php
public function unprepared(string $line): bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| line | string | SQL语句 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool | 返回查询是否成功的结果 |


## rawQuery

```php
public function rawQuery(string $line, array $params, int $fetch_mode): array|false
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| line | string | SQL语句 |
| params | array | 查询参数 |
| fetch_mode | int | fetch规则 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|false | 返回结果集或false |

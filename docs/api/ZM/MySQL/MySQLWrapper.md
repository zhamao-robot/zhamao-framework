# ZM\MySQL\MySQLWrapper

## __construct

```php
public function __construct(): mixed
```

### 描述

MySQLWrapper constructor.

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getDatabase

```php
public function getDatabase(): string
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## isAutoCommit

```php
public function isAutoCommit(): bool
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## setAutoCommit

```php
public function setAutoCommit(mixed $autoCommit): mixed
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| autoCommit | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## fetchAssociative

```php
public function fetchAssociative(string $query, array $params, array $types): array|false
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|false |  |


## fetchNumeric

```php
public function fetchNumeric(string $query, array $params, array $types): array|false
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|false |  |


## fetchOne

```php
public function fetchOne(string $query, array $params, array $types): false|mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| false|mixed |  |


## isTransactionActive

```php
public function isTransactionActive(): bool
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## delete

```php
public function delete(mixed $table, array $criteria, array $types): int
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| table | mixed |  |
| criteria | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## setTransactionIsolation

```php
public function setTransactionIsolation(mixed $level): int
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| level | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## getTransactionIsolation

```php
public function getTransactionIsolation(): int
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## update

```php
public function update(mixed $table, array $data, array $criteria, array $types): int
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| table | mixed |  |
| data | array |  |
| criteria | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## insert

```php
public function insert(mixed $table, array $data, array $types): int
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| table | mixed |  |
| data | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## quoteIdentifier

```php
public function quoteIdentifier(mixed $str): string
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| str | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## quote

```php
public function quote(mixed $value, int $type): mixed
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| value | mixed |  |
| type | int |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## fetchAllNumeric

```php
public function fetchAllNumeric(string $query, array $params, array $types): array
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## fetchAllAssociative

```php
public function fetchAllAssociative(string $query, array $params, array $types): array
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## fetchAllKeyValue

```php
public function fetchAllKeyValue(string $query, array $params, array $types): array
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## fetchAllAssociativeIndexed

```php
public function fetchAllAssociativeIndexed(string $query, array $params, array $types): array
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## fetchFirstColumn

```php
public function fetchFirstColumn(string $query, array $params, array $types): array
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## iterateNumeric

```php
public function iterateNumeric(string $query, array $params, array $types): Traversable
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Traversable |  |


## iterateAssociative

```php
public function iterateAssociative(string $query, array $params, array $types): Traversable
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Traversable |  |


## iterateKeyValue

```php
public function iterateKeyValue(string $query, array $params, array $types): Traversable
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Traversable |  |


## iterateAssociativeIndexed

```php
public function iterateAssociativeIndexed(string $query, array $params, array $types): Traversable
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Traversable |  |


## iterateColumn

```php
public function iterateColumn(string $query, array $params, array $types): Traversable
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| query | string |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Traversable |  |


## executeQuery

```php
public function executeQuery(mixed $sql, array $types, array $params, Doctrine\DBAL\Cache\QueryCacheProfile $qcp): ZM\MySQL\MySQLStatementWrapper
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| sql | mixed |  |
| types | array |  |
| params | array |  |
| qcp | Doctrine\DBAL\Cache\QueryCacheProfile |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\MySQL\MySQLStatementWrapper |  |


## executeCacheQuery

```php
public function executeCacheQuery(mixed $sql, mixed $params, mixed $types, Doctrine\DBAL\Cache\QueryCacheProfile $qcp): ZM\MySQL\MySQLStatementWrapper
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| sql | mixed |  |
| params | mixed |  |
| types | mixed |  |
| qcp | Doctrine\DBAL\Cache\QueryCacheProfile |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\MySQL\MySQLStatementWrapper |  |


## executeStatement

```php
public function executeStatement(mixed $sql, array $params, array $types): int
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| sql | mixed |  |
| params | array |  |
| types | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## getTransactionNestingLevel

```php
public function getTransactionNestingLevel(): int
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## lastInsertId

```php
public function lastInsertId(null $name): string
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| name | null |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## transactional

```php
public function transactional(Closure $func): mixed
```

### 描述

overwrite method to $this->connection->transactional()

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| func | Closure |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## setNestTransactionsWithSavepoints

```php
public function setNestTransactionsWithSavepoints(mixed $nestTransactionsWithSavepoints): mixed
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| nestTransactionsWithSavepoints | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getNestTransactionsWithSavepoints

```php
public function getNestTransactionsWithSavepoints(): bool
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## beginTransaction

```php
public function beginTransaction(): bool
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## commit

```php
public function commit(): bool
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## rollBack

```php
public function rollBack(): bool
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## createSavepoint

```php
public function createSavepoint(mixed $savepoint): mixed
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| savepoint | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## releaseSavepoint

```php
public function releaseSavepoint(mixed $savepoint): mixed
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| savepoint | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## rollbackSavepoint

```php
public function rollbackSavepoint(mixed $savepoint): mixed
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| savepoint | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## setRollbackOnly

```php
public function setRollbackOnly(): mixed
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## isRollbackOnly

```php
public function isRollbackOnly(): bool
```

### 描述

wrapper method

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## createQueryBuilder

```php
public function createQueryBuilder(): ZM\MySQL\MySQLQueryBuilder
```

### 描述

overwrite method to $this->connection->createQueryBuilder

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\MySQL\MySQLQueryBuilder |  |

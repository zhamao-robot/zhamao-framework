# ZM\Store\Database\DBWrapper

## __construct

```php
public function __construct(string $name): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| name | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getDatabase

```php
public function getDatabase(): string
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## isAutoCommit

```php
public function isAutoCommit(): bool
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## setAutoCommit

```php
public function setAutoCommit(bool $auto_commit): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| auto_commit | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## fetchAssociative

```php
public function fetchAssociative(string $query, array $params, array $types): mixed
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
| mixed |  |


## fetchNumeric

```php
public function fetchNumeric(string $query, array $params, array $types): mixed
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
| mixed |  |


## fetchOne

```php
public function fetchOne(string $query, array $params, array $types): mixed
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
| mixed |  |


## isTransactionActive

```php
public function isTransactionActive(): bool
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## delete

```php
public function delete(string $table, array $criteria, array $types): int
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| table | string |  |
| criteria | array |  |
| types | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## setTransactionIsolation

```php
public function setTransactionIsolation(int $level): int
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| level | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## getTransactionIsolation

```php
public function getTransactionIsolation(): int
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## update

```php
public function update(string $table, array $data, array $criteria, array $types): int
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| table | string |  |
| data | array |  |
| criteria | array |  |
| types | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## insert

```php
public function insert(string $table, array $data, array $types): int
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| table | string |  |
| data | array |  |
| types | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## quoteIdentifier

```php
public function quoteIdentifier(string $str): string
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| str | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## quote

```php
public function quote(mixed $value, mixed $type): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| value | mixed |  |
| type | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## fetchAllNumeric

```php
public function fetchAllNumeric(string $query, array $params, array $types): array
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
| array |  |


## fetchAllAssociative

```php
public function fetchAllAssociative(string $query, array $params, array $types): array
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
| array |  |


## fetchAllKeyValue

```php
public function fetchAllKeyValue(string $query, array $params, array $types): array
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
| array |  |


## fetchAllAssociativeIndexed

```php
public function fetchAllAssociativeIndexed(string $query, array $params, array $types): array
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
| array |  |


## fetchFirstColumn

```php
public function fetchFirstColumn(string $query, array $params, array $types): array
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
| array |  |


## iterateNumeric

```php
public function iterateNumeric(string $query, array $params, array $types): Traversable
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
| Traversable |  |


## iterateAssociative

```php
public function iterateAssociative(string $query, array $params, array $types): Traversable
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
| Traversable |  |


## iterateKeyValue

```php
public function iterateKeyValue(string $query, array $params, array $types): Traversable
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
| Traversable |  |


## iterateAssociativeIndexed

```php
public function iterateAssociativeIndexed(string $query, array $params, array $types): Traversable
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
| Traversable |  |


## iterateColumn

```php
public function iterateColumn(string $query, array $params, array $types): Traversable
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
| Traversable |  |


## executeQuery

```php
public function executeQuery(string $sql, array $params, array $types, Doctrine\DBAL\Cache\QueryCacheProfile $qcp): ZM\Store\Database\DBStatementWrapper
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| sql | string |  |
| params | array |  |
| types | array |  |
| qcp | Doctrine\DBAL\Cache\QueryCacheProfile |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\Store\Database\DBStatementWrapper |  |


## executeCacheQuery

```php
public function executeCacheQuery(string $sql, array $params, array $types, Doctrine\DBAL\Cache\QueryCacheProfile $qcp): ZM\Store\Database\DBStatementWrapper
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| sql | string |  |
| params | array |  |
| types | array |  |
| qcp | Doctrine\DBAL\Cache\QueryCacheProfile |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\Store\Database\DBStatementWrapper |  |


## executeStatement

```php
public function executeStatement(string $sql, array $params, array $types): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| sql | string |  |
| params | array |  |
| types | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getTransactionNestingLevel

```php
public function getTransactionNestingLevel(): int
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## lastInsertId

```php
public function lastInsertId(string $name): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| name | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## transactional

```php
public function transactional(Closure $func): mixed
```

### 描述

作者很懒，什么也没有说

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
public function setNestTransactionsWithSavepoints(bool $nest_transactions_with_savepoints): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| nest_transactions_with_savepoints | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getNestTransactionsWithSavepoints

```php
public function getNestTransactionsWithSavepoints(): bool
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## beginTransaction

```php
public function beginTransaction(): bool
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## commit

```php
public function commit(): bool
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## rollBack

```php
public function rollBack(): bool
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## createSavepoint

```php
public function createSavepoint(string $savepoint): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| savepoint | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## releaseSavepoint

```php
public function releaseSavepoint(string $savepoint): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| savepoint | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## rollbackSavepoint

```php
public function rollbackSavepoint(string $savepoint): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| savepoint | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## setRollbackOnly

```php
public function setRollbackOnly(): mixed
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## isRollbackOnly

```php
public function isRollbackOnly(): bool
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## createQueryBuilder

```php
public function createQueryBuilder(): ZM\Store\Database\DBQueryBuilder
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\Store\Database\DBQueryBuilder |  |


## getConnectionClass

```php
public function getConnectionClass(string $type): string
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| type | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |

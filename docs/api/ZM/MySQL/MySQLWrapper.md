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
public function setAutoCommit(bool $auto_commit): mixed
```

### 描述

wrapper method

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
public function delete(string $table, array $criteria, array $types): int
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| table | string | 表 |
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

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| level | int | Sets the transaction isolation level |

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
public function update(string $table, array $data, array $criteria, array $types): int
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| table | string | 表名 |
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

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| table | string | 表名 |
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

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| str | string | The name to be quoted |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## quote

```php
public function quote(mixed $value, null|int|string|Type $type): mixed
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| value | mixed |  |
| type | null|int|string|Type |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


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
public function lastInsertId(null|string $name): false|int|string
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| name | null|string | name of the sequence object from which the ID should be returned |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| false|int|string | a string representation of the last inserted ID |


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
public function setNestTransactionsWithSavepoints(bool $nest_transactions_with_savepoints): mixed
```

### 描述

wrapper method

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
public function createSavepoint(string $savepoint): mixed
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| savepoint | string | the name of the savepoint to create |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## releaseSavepoint

```php
public function releaseSavepoint(string $savepoint): mixed
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| savepoint | string | the name of the savepoint to release |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## rollbackSavepoint

```php
public function rollbackSavepoint(string $savepoint): mixed
```

### 描述

wrapper method

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| savepoint | string | the name of the savepoint to rollback to |

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

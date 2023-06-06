# 数据库

## 简介

炸毛框架的数据库组件对接了 MySQL、SQLite、PostgreSQL 连接池，在使用过程中无需配置即可实现 SQL 查询，同时拥有高并发。

目前 2.5 版本后炸毛框架底层采用了 `doctrine/dbal` 组件，可以方便地构建 SQL 语句。

本章大体查询内容均以下表 `users` 为基础：

| id | username | gender | update_time |
| -- | -------- | ------ | ----------- |
| 1 | jack | man | 2021-10-12 |
| 2 | rose | woman | 2021-10-11 |

## 连接池

炸毛框架的数据库组件支持原生 SQL、查询构造器，去掉了复杂的对象模型关联，同时默认为数据库连接池，使开发变得简单。

数据库的配置位于  `config/global.php` 文件的 `database` 段，框架支持多个数据库的连接，其中字段示例如下，下面两段分别为使用 SQLite、MySQL 时的配置项：

```php
/* MySQL 和 SQLite3 数据库连接配置，框架将自动生成连接池，支持多个连接池 */
$config['database'] = [
    'sqlite_db1' => [       // 数据库连接池名称
        'enable' => false,  // 是否启用
        'type' => 'sqlite', // 类型，支持 sqlte、mysql、pgsql 三种
        'dbname' => 'a.db', // 当类型为 sqlite 时，dbname 字段为 SQLite 数据库的文件名
        'pool_size' => 10,  // 连接池大小（防止协程冲突，SQLite 也需要连接池）
    ],
    'default' => [
        'enable' => false,
        'type' => 'mysql',
        'host' => '127.0.0.1', // 填写数据库服务器地址后才会创建数据库连接
        'port' => 3306,
        'username' => 'root',
        'password' => 'ZhamaoTEST',
        'dbname' => 'zm',
        'charset' => 'utf8mb4',
        'pool_size' => 64,
    ],
];
```

其中，type 为 mysql 和 pgsql 时，需要设置 host、port、username、password、dbname 字段，sqlite 时需要设置 dbname 字段。

在设置了 enable 为 true 后，将创建对应数据库的连接池。在框架所有插件加载后启用前会创建连接池。

## 连接池模式

框架对于不同种类的 SQL 采用了统一的 wrapper 层，保证不同数据库调用时的接口尽可能相同。从连接池拿取对象很简单，通过方法 `db()`：

```php
// 获取 default 名称的数据库连接
$db = db();
// 获取对应名称的数据库连接，名称等于上方配置中的键名
$sqlite = db('sqlite_db1');
```

返回的对象为 `DBWrapper` 对象。

## 便捷 SQLite 模式

对于 SQLite 数据库来说，使用连接池可能较为笨重，而且在开发者使用框架开发炸毛框架的插件分发时，可能需要使用 SQLite 数据库，但是又不想使用连接池。

框架在 3.2.0 版本开始提供了便捷 SQLite 访问，无需任何配置，仅需 `zm_sqlite('dbname.db')` 方式即可创建和访问一个 SQLite 数据库。

```php
// 连接一个 SQLite 数据库，在相对路径下，文件会保存到 zm_data/db/ 目录
$db = zm_sqlite('a.db');
// 连接一个 SQLite 数据库，可以是任意绝对路径
$db = zm_sqlite('/home/zhamao/a.db');
// 在连接 SQLite 文件时，如果设置了 create_new 参数为 False，文件不存在时将会抛出异常
$db = zm_sqlite('a.db', create_new: false);
// 在连接 SQLite 文件时，如果设置了 keep_alive 参数为 False，框架将不会缓存已经打开的 PDO 对象，而是每次都会重新打开。（默认为 True，为了提升性能）
$db = zm_sqlite('a.db', keep_alive: false);
```

返回的对象为 `DBWrapper` 对象。

::: tip 提示

无论是使用连接池的 `db()` 还是便捷 SQLite 模式的 `zm_sqlite()`，获取的都是 `DBWrapper` 对象，文档只是为了书写方便。
实际使用过程中如果要使用便捷 SQLite 模式只需将 `db` 替换为 `zm_sqlite` 即可。

:::

### 执行预处理 SQL 语句

预处理查询很巧妙地解决了 SQL 注入问题，并且可以方便地绑定参数进行查询。

预处理一般是指使用 `?` 占位符或 `:xxx` 命名标签进行参数留空，先处理 SQL 语句再填入数据。

一般 `?` 具有前后位置性，例如如下的查询：

```php
$sql = "SELECT * FROM users WHERE id = ? AND username = ?";
$stmt = db()->getConnection()->prepare($sql);
$stmt->bindValue(1, "1");
$stmt->bindValue(2, "jack");
$resultSet = $stmt->executeQuery();
```

其中 `$resultSet` 与 `Statement` 方法相似，此处的对象可能是 [数据库语句对象 - TODO]() 或 数据库结果对象（结果对象与语句对象的 fetchXXX() 部分一致）。

这里也可以使用命名标签，使用标签可以给相同参数处使用同一个标签：

```php
$sql = "SELECT * FROM users WHERE gender = :name OR username = :name";
$stmt = db()->getConnection()->prepare($sql);
$stmt->bindValue("name", "jack");
$resultSet = $stmt->executeQuery();
dump($resultSet->fetchAllAssociative());
// 返回多行数据，使用关联数组返回
```

### 执行常规语句

执行常规语句为 statement 方式执行，此方法执行后只返回影响的行数，而不返回结果，适用于 UPDATE 等语句。

```php
$count = db()->executeStatement('UPDATE users SET username = ? WHERE id = ?', array('jwage', 1));
echo $count; // 1
```

### 执行查询语句

为给定的 SQL 创建一个准备好的语句并将参数传递给 executeQuery 方法，然后返回结果集。此方法为上述的「预处理查询语句」的简化版，可直接在第二个参数使用 array 插入绑定参数执行。

```php
$resultSet = db()->executeQuery('SELECT * FROM user WHERE username = ?', array('jack'));
$user = $resultSet->fetchAssociative();

/* $user 值
array(
    0 => array(
        'id' => 1,
        'username' => 'jack',
        'gender' => 'man',
        'update_time' => '2021-10-12'
    )
)
*/
```

## 助手函数

助手函数的意义在于简化 SQL 查询时调用过多次成员方法，达到一步就位的作用。

### fetchAllAssociative()

执行查询并将所有结果返回一个数组中。

```php
$resultSet = db()->fetchAllAssociative('SELECT * FROM user WHERE username = ?', array('jack'));
// 结果同 executeQuery()->fetchAllAssociative() 中 $user 的值。
```

### fetchAllKeyValue()

执行查询并将前两列分别作为键和值提取到关联数组中。

```php
$resultSet = db()->fetchAllKeyValue('SELECT username, gender FROM user WHERE username = ?', array('jack'));

/* $resultSet 值
array(
    'jack' => 'man'
)
 */
```

### fetchAllAssociativeIndexed()

执行查询并将数据作为关联数组获取，其中键代表第一列，值是其余列及其值的关联数组。

```php
$users = db()->fetchAllAssociativeIndexed('SELECT id, username, gender FROM users');

/*
array(
    1 => array(
        'username' => 'jack',
        'gender' => 'man',
        'update_time' => '2021-10-12'
    )
)
*/
```

### fetchNumeric()

查询并返回第一行数据，形式以数字索引方式返回每一列。

```php
$user = db()->fetchNumeric('SELECT * FROM users WHERE username = ?', array('jack'));

/*
array(
    0 => 'jwage',
    1 => 'man',
    2 => '2021-10-12'
)
*/
```

### fetchOne()

仅返回查询结果的第一行第一列的值。

```php
$username = db()->fetchOne('SELECT username FROM users WHERE id = ?', array(1));
echo $username; // jack
```

### fetchAssociative()

返回结果内第一行的关联数组形式的数据。

```php
$users = db()->fetchAssociative('SELECT * FROM users');

/*
array(
    'id' => 1,
    'username' => 'jack',
    'gender' => 'man',
    'update_time' => '2021-10-12'
)
*/
```

### delete()

删除查询操作，第一个参数为表名，第二个参数为 `['列名' => '列值']`。

```php
db()->delete('users', array('username' => 'jack'));
// 等同于执行DELETE FROM user WHERE username = ? ，参数列表为('jack')
```

### insert()

插入数据库一行，第一个参数为表名，第二个参数为对应数据。

```php
db()->insert('users', array('id' => 0, 'username' => 'jwage', 'gender' => 'woman', 'update_time' => '2021-10-17'));
// INSERT INTO user (id, username, gender, update_time) VALUES (?,?,?,?) (0,jwage,woman,2021-10-17)
```

### update()

更新数据库，使用给定数据更新匹配键值标识符的所有行。

```php
$wrapper->update('user', array('username' => 'jwage'), array('id' => 1));
// UPDATE user (username) VALUES (?) WHERE id = ? (jwage, 1)
```

## 使用查询构造器

有时候并不愿意写 SQL 语句，那么你可以选择使用 SQL 查询构造器。

> 此处由 [Doctrine 原英文文档](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.13/reference/query-builder.html#sql-query-builder) 翻译而来，如有不正确，可看原文档。

```php
$resultSet = sql_builder()->select(['username', 'gender'])->from('users')->where('username = ?')->setParameter(0, 'jack')->execute();
```

### 获取 SQL Builder

连接池的访问模式，使用全局函数 `sql_builder()` 即可。便捷 SQLite 模式，使用全局函数 `zm_sqlite_builder()` 即可。

```php
// 获取 default 名称的数据库连接的 builder
$queryBuilder = sql_builder();
// 获取对应名称的数据库连接的 builder，名称等于上方配置中的键名
$queryBuilder = sql_builder('sqlite_db1');
// 使用便捷 SQLite 模式获取 builder
$queryBuilder = zm_sqlite_builder('mydb.db');
// 在使用便捷 SQLite 模式时，也可以传入 create_new 参数和 keep_alive 参数
$queryBuilder = zm_sqlite_builder('/home/a/d.db', create_new: false, keep_alive: false);
```

### 构建一个普通查询

sql_builder 支持构建 INSERT、UPDATE、SELECT、DELETE 类型的查询语句，具体构造内容取决于你调用的方法。

对于 INSERT、UPDATE、DELETE 查询，你可以传入表名。

```php
$queryBuilder
    ->insert('users')
;

$queryBuilder
    ->update('users')
;

$queryBuilder
    ->delete('users')
;
```

你可以随时调用 `getSQL()` 方法获取当前状态下构造的 SQL 语句。

### DISTINCT 仅选择不同的值

如果你在使用查询器时，在 SELECT 模式下，可以使用 distinct 方法筛选独特的值列表：

```php
$queryBuilder
    ->select('name')
    ->distinct()
    ->from('users')
;
```

### WHERE 限定查询范围

在 SELECT、UPDATE、DELETE 查询模式下，可以使用 where 方法限定选择条件：

```php
$queryBuilder
    ->select('id', 'name')
    ->from('users')
    ->where('email = ?')
;
```

> 当调用 `where()` 后将会清除先前通过 `andWhere()`、`orWhere()` 添加的 WHERE 条件，所以你应该先使用 `where()`，再使用 `andWhere()` 或 `orWhere()` 等条件限定方法。

### 表别名

基于 SQL 语法：`SELECT u.id, u.name FROM users AS u`，你可以使用 `from()` 方法指定表的别名：

```php
$queryBuilder
    ->select('u.id', 'u.name')
    ->from('users', 'u')
    ->where('u.email = ?')
;
```

### 使用 GROUP BY 和 HAVING 语句

SELECT 查询下，你可以使用 `having()` 和 `groupBy()` 等方法来代表 `GROUP_BY`、`HAVING` 等查询语法。
你也可以在使用 `where()` 限定查询条件时与 `andHaving()`、`orHaving()` 等方式组合谓语。

对于 `GROUP BY` 语法，你可以使用 `groupBy()` 方法来指定：

```php
$queryBuilder
    ->select('DATE(last_login) as date', 'COUNT(id) AS users')
    ->from('users')
    ->groupBy('DATE(last_login)')
    ->having('users > 10')
;
```

### Join 语句

SELECT 查询下，你可以生成多种不同的 JOIN 查询语句：INNER、LEFT、RIGHT。需要注意的是，RIGHT 的 JOIN 方式可能不适用于所有平台，例如 SQLite。

一个 JOIN 语句必须是 FROM 语句的一部分，这块学过 SQL 的人应该都知道怎么用，但我实在不会翻译了，翻译软件是个废物，贴上来原文：

> A join always belongs to one part of the from clause. This is why you have to specify the alias of the FROM part the join belongs to as the first argument.
> 
> As a second and third argument you can then specify the name and alias of the join-table and the fourth argument contains the ON clause.

```php
$queryBuilder
    ->select('u.id', 'u.name', 'p.number')
    ->from('users', 'u')
    ->innerJoin('u', 'phonenumbers', 'p', 'u.id = p.user_id')
;
```

::: tip 提示

TODO：我还没翻译完，东西挺多的，如果着急看这部分内容的话可以先看 [原文](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.13/reference/query-builder.html#sql-query-builder)。

:::

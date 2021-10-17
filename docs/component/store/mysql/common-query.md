# 执行 SQL 语句

在一开始，无论你做什么数据库操作，均需要获取一个 `\ZM\MySQL\MySQLWrapper` 作为你的操作对象。

```php
/** @var \ZM\MySQL\MySQLWrapper $wrapper */
$wrapper = \ZM\MySQL\MySQLManager::getWrapper();
```

## 执行 SQL 语句查询

使用方法 `query()` 即可执行最基本的 SQL 语句查询。

!!! warning "警告"

    不推荐使用此方法，因为此方法容易造成 SQL 注入等安全问题，除非你知道你在做什么！

```php
$wrapper = \ZM\MySQL\MySQLManager::getWrapper();
$sql = "SELECT * FROM users";
$stmt = $wrapper->executeQuery($sql);
```

`MySQLWrapper->query()` 方法返回一个查询语句对象，此对象保存了数据库语句查询的结果。

有关查询对象的相关方法说明，见 [数据库语句对象](../mysql-statement)。

```php
while (($row = $stmt->fetchAssociative()) !== false) {
    echo $row['username'] . PHP_EOL; // 返回 jack [换行] rose
}
```

此方式有以下缺点不推荐使用：

- 易造成 SQL 注入安全问题
- 将数据直接写入裸 SQL 是一项繁琐的操作，效率不高

一般情况如果需要手写 SQL 语句进行查询，建议使用下面的预处理方式进行 SQL 查询。

## 执行预处理 SQL 语句

预处理查询很巧妙地解决了 SQL 注入问题，并且可以方便地绑定参数进行查询。

预处理一般是指使用 `?` 占位符或 `:xxx` 命名标签进行参数留空，先处理 SQL 语句再填入数据。

一般 `?` 具有前后位置性，例如如下的查询：

```php
$sql = "SELECT * FROM users WHERE id = ? AND username = ?";
$stmt = $wrapper->getConnection()->prepare($sql);
$stmt->bindValue(1, "1");
$stmt->bindValue(2, "jack");
$resultSet = $stmt->executeQuery();
```

其中 `$resultSet` 与 `Statement` 方法相似，此处的对象可能是 [数据库语句对象](../mysql-statement) 或 数据库结果对象（结果对象与语句对象的 `fetchXXX()` 部分一致）。

这里也可以使用命名标签，使用标签可以给相同参数处使用同一个标签：

```php
$sql = "SELECT * FROM users WHERE gender = :name OR username = :name";
$stmt = $wrapper->getConnection()->prepare($sql);
$stmt->bindValue("name", "jack");
$resultSet = $stmt->executeQuery();
```

## 执行常规语句

执行常规语句为 `statement` 方式执行，此方法执行后只返回影响的行数，而不返回结果，适用于 `UPDATE` 等语句。

```php
<?php
$count = $wrapper->executeStatement('UPDATE users SET username = ? WHERE id = ?', array('jwage', 1));
echo $count; // 1
```

## 执行查询语句

为给定的 SQL 创建一个准备好的语句并将参数传递给 executeQuery 方法，然后返回结果集。此方法为上述的「预处理查询语句」的简化版，可直接在第二个参数使用 array 插入绑定参数执行。

```php
$resultSet = $wrapper->executeQuery('SELECT * FROM user WHERE username = ?', array('jack'));
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

### fetchAllAssociative()

执行查询并将所有结果返回一个数组中。

因此，上面的查询语句还可以直接被简化为一次方法调用：

```php
$resultSet = $wrapper->fetchAllAssociative('SELECT * FROM user WHERE username = ?', array('jack'));
// 结果同 executeQuery()->fetchAllAssociative() 中 $user 的值。
```

### fetchAllKeyValue()

执行查询并将前两列分别作为键和值提取到关联数组中。

```php
$resultSet = $wrapper->fetchAllKeyValue('SELECT username, gender FROM user WHERE username = ?', array('jack'));

/* $resultSet 值
array(
    'jack' => 'man'
)
 */
```

### fetchAllAssociativeIndexed()

执行查询并将数据作为关联数组获取，其中键代表第一列，值是其余列及其值的关联数组。

```php
$users = $wrapper->fetchAllAssociativeIndexed('SELECT id, username, gender FROM users');

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
$user = $wrapper->fetchNumeric('SELECT * FROM users WHERE username = ?', array('jack'));

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
$username = $wrapper->fetchOne('SELECT username FROM users WHERE id = ?', array(1));
echo $username; // jack
```

### fetchAssociative()

返回结果内第一行的关联数组形式的数据。

```php
$users = $wrapper->fetchAssociative('SELECT * FROM users');

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
<?php
$wrapper->delete('users', array('username' => 'jack'));
// 等同于执行DELETE FROM user WHERE username = ? ，参数列表为('jack')
```

### insert()

插入数据库一行，第一个参数为表名，第二个参数为对应数据。

```php
$wrapper->insert('users', array('id' => 0, 'username' => 'jwage', 'gender' => 'woman', 'update_time' => '2021-10-17'));
// INSERT INTO user (id, username, gender, update_time) VALUES (?,?,?,?) (0,jwage,woman,2021-10-17)
```

### update()

更新数据库，使用给定数据更新匹配键值标识符的所有行。

```php
<?php
$wrapper->update('user', array('username' => 'jwage'), array('id' => 1));
// UPDATE user (username) VALUES (?) WHERE id = ? (jwage, 1)
```


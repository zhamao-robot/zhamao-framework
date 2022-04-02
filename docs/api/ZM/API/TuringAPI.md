# ZM\API\TuringAPI

## getTuringMsg

```php
public function getTuringMsg(string $msg, int|string $user_id, string $api): string
```

### 描述

请求图灵API，返回图灵的消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | string | 消息 |
| user_id | int|string | 用户ID |
| api | string | API Key |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | 图灵的回复 |


## getResultStatus

```php
public function getResultStatus(array $r): bool|string
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| r | array | 数据API回包 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool|string | 错误消息或成功鸥鸟 |

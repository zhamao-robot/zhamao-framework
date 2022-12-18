# ZM\Utils\HttpUtil

## parseUri

```php
public function parseUri(Psr\Http\Message\RequestInterface $request, mixed $node, mixed $params): int
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| request | Psr\Http\Message\RequestInterface |  |
| node | mixed |  |
| params | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int |  |


## handleStaticPage

```php
public function handleStaticPage(string $uri, array $settings): Psr\Http\Message\ResponseInterface
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| uri | string |  |
| settings | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Psr\Http\Message\ResponseInterface |  |


## handleHttpCodePage

```php
public function handleHttpCodePage(int $code): Psr\Http\Message\ResponseInterface
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| code | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Psr\Http\Message\ResponseInterface |  |


## createJsonResponse

```php
public function createJsonResponse(array $data, int $http_code, int $json_flag): Psr\Http\Message\ResponseInterface
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| data | array |  |
| http_code | int |  |
| json_flag | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Psr\Http\Message\ResponseInterface |  |

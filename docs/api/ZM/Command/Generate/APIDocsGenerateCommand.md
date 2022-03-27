# ZM\Command\Generate\APIDocsGenerateCommand

## configure

```php
public function configure(): void
```

### 描述

Configures the current command.

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## execute

```php
public function execute(Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output): int
```

### 描述

Executes the current command.
This method is not abstract because you can use this class
as a concrete class. In this case, instead of defining the
execute() method, you set the code to execute by passing
a Closure to the setCode() method.

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| input | Symfony\Component\Console\Input\InputInterface |  |
| output | Symfony\Component\Console\Output\OutputInterface |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int | 0 if everything went fine, or an exit code * |


## getClassMetas

```php
public function getClassMetas(string $class_name, Jasny\PhpdocParser\PhpdocParser $parser): array
```

### 描述

获取类的元数据
包括类的注释、方法的注释、参数、返回值等

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| class_name | string |  |
| parser | Jasny\PhpdocParser\PhpdocParser |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## convertMetaToMarkdown

```php
public function convertMetaToMarkdown(string $method, array $meta): string
```

### 描述

将方法的元数据转换为 Markdown 格式

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| method | string | 方法名 |
| meta | array | 元数据 |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |

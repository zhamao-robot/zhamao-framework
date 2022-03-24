# 命令帮助生成器 - CommandHelpGenerator

类定义：`\ZM\Utils\MessageUtil`
目前包含在 `MessageUtil` 类中，日后可能会进行拆分。

> 2.7.3 版本起可用。

## 方法

### generateCommandHelp

自动扫描定义的所有命令，生成注解树，并以此生成命令列表及帮助。

第一次运行时，会遍历一遍注解树并进行生成，此后会从缓存中读取。

定义：`generateCommandHelp()`

返回值：`array` 每个元素对应一个命令的帮助信息，格式为：`命令名（其他触发条件）：命令描述`

示例：`天气（温度、包含“天气”）：查询指定城市的天气`

```php
/**
 * 输出帮助信息
 *
 * @CQCommand("帮助")
 */
#[CQCommand('帮助')]
public function help(): string
{
    $helps = MessageUtil::generateCommandHelp();
    array_unshift($helps, '帮助：');
    return implode("\n", $helps);
}
```

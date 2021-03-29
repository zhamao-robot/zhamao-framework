# CQBefore 过滤不了 waitMessage

因为 `waitMessage()` 功能是要等待接收下一个消息事件的，而消息事件又会被 CQBefore 走一遍。但是这里就会有一个问题，那 `waitMessage()` 的消息会不会走 CQBefore 呢？（显然不会啊！这个问题的题目就是这个！）

框架在 2.4.2 版本之前是无法过滤 waitMessage() 的（之前在 2.1 版本左右的几个版本是可以的，但这里不讨论历史版本），从 2.4.2 版本起支持过滤 `waitMessage`，但是需要设置一下 `@CQBefore` 的级别。

```php
/**
 * @CQBefore("message",level=201)
 */
public function filter1() {
    return true;
}

/**
 * @CQBefore("message")
 */
public function filter2() {
    return true;
}
```

如果 `level >= 200`，那么此注解事件则会过滤 `waitMessage()`，如果 `level < 200`，则不会。（`@CQBefore` 的默认 level 为 20，所以默认情况下是不会过滤 waitMessage 的）
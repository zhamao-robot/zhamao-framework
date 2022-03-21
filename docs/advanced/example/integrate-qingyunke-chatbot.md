# 接入青云客智能聊天机器人API

作为一个群聊机器人，懂得聊天会让机器人增色不少，在大数据和 AI 热潮下，不少厂商都研发了自己的智能聊天 API，例如图灵机器人、腾讯智能闲聊等，大厂开发的 API 自然有着他人无可比拟的健壮性和可靠性，但是随之而来不菲的价格显然并不适合大众开发者。这时候一个免费、可用的智能聊天 API 便非常重要了，其中，青云客是少有的完全免费、无需注册的智能聊天 API，提供了包括智能聊天、歌词、天气查询、笑话等多种有用功能，且接入简单，非常适合新手开发者尝试。

## 结果演示

![圖片](https://user-images.githubusercontent.com/31698606/158875192-108698a3-b54e-4fc0-889a-0829ca328b13.png)

## 阅读接入指南

不管接入何种服务，阅读接入指南永远都是最优先、最重要的一步，所幸青云客的接入指南十分简单，简单来说归纳为以下：
* 请求：GET https://api.qingyunke.com/api.php
* 参数：
* * `key`   目前固定为 `free`
* * `appid` 目前固定为 `0`
* * `msg`   关键词，需要经过 `urlencode`
* 注意：返回结果中 `{br}` 代表换行

## 逻辑编写

阅读过后，我们便可以进行主要的编写工作了。

首先，为了机器人的性能考虑，也为了避免过分打扰群聊的聊天，我们希望机器人只有在主动触发（@AT 或者 关键词等）时才会进行智能聊天。

对于关键词匹配，我们可以使用 `@CQCommand`：

```php
/**
 * 智能聊天
 *
 * @CQCommand(start_with="机器人")
 */
public function chat()
{
    // 替换掉机器人前缀，并获取消息内容
    $msg = ctx()->getMessage();
    $msg = str_replace('机器人', '', $msg);
    if (empty(trim($msg))) {
        $msg = ctx()->getFullArg('怎么了？');
    }

    Console::info('正在获取智能聊天回复：' . $msg);
    // 请求 API 获取回复
    $raw_data = file_get_contents('https://api.qingyunke.com/api.php?key=free&appid=0&msg=' . urlencode($msg));
    try {
        $data = json_decode($raw_data, true, 512, JSON_THROW_ON_ERROR);
    } catch (\Exception $e) {
        $data = ['content' => '机器人解析异常，请稍后再试'];
        Console::warning('无法获取智能聊天回复：' . $e->getMessage());
    }
    if ($data['result'] !== 0) {
        $data = ['content' => '机器人服务异常，请稍后再试'];
        Console::warning('无法获取智能聊天回复：' . $raw_data);
    }
    Console::info('获取智能聊天回复完成：' . $data['content']);
    // 将 {br} 替换为换行
    $data['content'] = strtr($data['content'], ['{br}' => "\n"]);
    return $data['content'];
}
```

这样我们的命令便只会在用户发送以`机器人`开头的消息时才会触发。

同时，我们也希望在 @AT 机器人时也进行回复，此时可以使用 `@CQBefore` 方法进行折中：

```php
/**
 * 将 AT 机器人的消息交由智能聊天处理
 *
 * @CQBefore("message")
 */
public function changeAt(): bool
{
    // 判断此条消息是否 AT 了机器人
    if (MessageUtil::isAtMe(ctx()->getMessage(), ctx()->getRobotId())) {
        // 将 AT 本身从消息中去掉
        $msg = str_replace(CQ::at(ctx()->getRobotId()), '', ctx()->getMessage());
        ctx()->setMessage('机器人' . trim($msg));
        // 调用智能聊天
        ctx()->reply($this->chat());
        return false;
    }
    return true;
}
```

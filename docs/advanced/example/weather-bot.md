# 基于词性分析和魅族天气的天气查询机器人

本文将基于 [`jieba-php`](https://github.com/fukuball/jieba-php) 中文分词库以及 [魅族天气 API](https://github.com/shichunlei/-Api/blob/master/MeizuWeather.md) 开发一个天气查询机器人。

## 结果演示

![圖片](https://user-images.githubusercontent.com/31698606/159122016-61ba9696-5786-4561-b371-827d9f1d01aa.png)
尾部的随机表情并非本教程的一部分。

## 逻辑编写

[jieba-php](https://github.com/fukuball/jieba-php) 是目前比较好用的中文分词库，虽然最近的维护并不活跃，但已足够我们的需求：

```shell
composer require fukuball/jieba-php:dev-master
```

以下代码使用了本文作者自行编写的天气查询库，需要进行引入：

```shell
composer require sunxyw/weather
```

您也可以将以下代码自行改写为直接调用魅族天气 API，详情请参阅[魅族天气 API 文档](https://github.com/shichunlei/-Api/blob/master/MeizuWeather.md)。

```php
<?php

namespace Bot\Module\SmartChat;

use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Posseg;
use Sunxyw\Weather\Weather;
use ZM\Annotation\CQ\CQCommand;
use ZM\Console\Console;

class WeatherReport
{
    /**
     * 加载字典
     *
     * @OnStart(worker_id=-1)
     *
     * @return void
     */
    public function initDictionary(): void
    {
        // 分词以及词性分析需要载入字典到内存
        ini_set('memory_limit', '600M');
        Jieba::init(['dict' => 'small']);
        Posseg::init();
    }

    /**
     * 查询天气
     *
     * @CQCommand(keyword="天气")
     *
     * @return string
     */
    public function cmdQueryWeather(): string
    {
        // 分词并进行词性分析
        $seg_list = Posseg::cut(ctx()->getMessage());
        $tags = array_column($seg_list, 'tag');
        // 找出词性为 ns（地名）的单词
        $location_index = array_search('ns', $tags, true);
        $location = $seg_list[$location_index]['word'];

        // 此处引入了本文作者自己写的天气库
        $w = new Weather();
        try {
            $report = $w->getWeather($location);
        } catch (\InvalidArgumentException) {
            return '城市输入错误';
        } catch (\JsonException $e) {
            Console::warning("天气查询失败：{$e->getMessage()}");
            return '天气查询失败';
        }

        $template = <<<EOF
%s天气：%s
温度：%s℃
湿度：%s%%
风向：%s %s
空气质量：%s
------------------------------
未来三天天气：
%s：%s，日间%s℃，夜间%s℃，吹%s %s
%s：%s，日间%s℃，夜间%s℃，吹%s %s
%s：%s，日间%s℃，夜间%s℃，吹%s %s
EOF;
        $args = [
            $report->getCity(),
            $report->getRealtime()['weather'],
            $report->getRealtime()['temperature'],
            $report->getRealtime()['humidity'],
            $report->getRealtime()['wind_direction'],
            $report->getRealtime()['wind_speed'],
            $report->getRealtime()['air_quality'],
        ];
        foreach (array_slice($report->getForecastDaily(), 0, 3) as $forecast) {
            $args[] = $forecast['date'];
            $args[] = $forecast['weather'];
            $args[] = $forecast['temperature']['day'];
            $args[] = $forecast['temperature']['night'];
            $args[] = $forecast['wind_direction'];
            $args[] = $forecast['wind_speed'];
        }
        return vsprintf($template, ...$args);
    }
}
```

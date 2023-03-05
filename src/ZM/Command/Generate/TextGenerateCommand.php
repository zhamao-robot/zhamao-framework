<?php

declare(strict_types=1);

namespace ZM\Command\Generate;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use ZM\Command\Command;
use ZM\Utils\ZMRequest;

#[AsCommand(name: 'generate:text', description: '生成一些文本（内部）')]
class TextGenerateCommand extends Command
{
    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, '生成的文本内容');
        $this->setDescription('生成一些框架本身的文本（内部' . PHP_EOL . '当前包含：class-alias-md，update-log-md');
    }

    /**
     * {@inheritDoc}
     */
    protected function handle(): int
    {
        return match ($this->input->getArgument('name')) {
            'class-alias-md' => $this->generateClassAliasDoc(),
            'update-log-md' => $this->generateUpdateLogs(),
            default => static::FAILURE,
        };
    }

    private function generateClassAliasDoc(): int
    {
        $file = file_get_contents(FRAMEWORK_ROOT_DIR . '/src/Globals/global_class_alias.php');
        // 提取class_alias函数的参数
        preg_match_all('/class_alias\((.+?), \'(.+?)\'\);/', $file, $matches);
        $full_maxlen = 0;
        $short_maxlen = 0;
        $line = [];
        foreach ($matches[1] as $k => $v) {
            $full_class = substr($v, 0, -7);
            $short_class = $matches[2][$k];
            $line[] = [$full_class, $short_class];
            $full_maxlen = max($full_maxlen, strlen('`' . $full_class . '`'));
            $short_maxlen = max($short_maxlen, strlen('`' . $short_class . '`'));
        }
        $obj = <<<'LINE'
# 类全局别名

在框架 1.x 和 2.x 老版本中，我们发现许多开发者在使用框架时，往往不会使用 PhpStorm 这类大型 IDE，而即使使用 VSCode 这类编辑器的时候也不一定会安装补全插件，
这样在编写机器人模块或插件时会因寻找每个对象的完整命名空间而烦恼。

在 3.0 版本起，框架对常用的注解事件和对象均使用了类别名功能，方便非 IDE 开发者编写插件。

## 别名使用

框架对别名的定义比较简单，由于内部暂时没有不同命名空间下重复类名的情况，所以我们目前只对需要别名类名的命名空间移除，例如：

`\ZM\Annotation\OneBot\BotCommand` 注解事件类，在经过全局别名后，你也可以使用 `\BotCommand` 作为注解事件，效果相同。

## 别名列表
LINE;
        file_put_contents(
            SOURCE_ROOT_DIR . '/docs/components/common/class-alias.md',
            $obj . "\n" . '| ' . str_pad('全类名      ', $full_maxlen + 6) . ' | ' . str_pad('别名    ', $short_maxlen + 4) . ' |' .
            "\n" . '|-' . str_pad('', $full_maxlen, '-') . '-|-' . str_pad('', $short_maxlen, '-') . '-|' .
            "\n" . implode("\n", array_map(fn ($v) => '| ' . str_pad('`' . $v[0] . '`', $full_maxlen) . ' | ' . str_pad('`' . $v[1] . '`', $short_maxlen) . ' |', $line)) . "\n"
        );
        $this->write('成功');
        return static::SUCCESS;
    }

    private function generateUpdateLogs(): int
    {
        date_default_timezone_set(config('global.runtime.timezone', 'UTC'));
        $api = ZMRequest::get('https://api.github.com/repos/zhamao-robot/zhamao-framework/releases', ['User-Agent' => 'ZMFramework']);
        if ($api === false) {
            $this->error('获取更新日志失败');
            return static::FAILURE;
        }
        $json = json_decode($api, true);
        $line = '# 更新日志' . "\r\n\r\n> 本页面由框架命令 `./zhamao generate:text update-log-md` 自动生成\r\n\r\n";
        foreach ($json as $v) {
            $version = $v['tag_name'];
            if (str_starts_with($version, '2.')) {
                continue;
            }
            $doc_count = 0;
            $time = '> 更新时间：' . date('Y-m-d', strtotime($v['published_at']));
            $line .= '## v' . $v['tag_name'] . "\r\n\r\n" . $time . "\r\n\r\n";
            $v['body'] = trim(str_replace("## What's Changed", '', $v['body']));
            $bodies = explode("\r\n", $v['body']);
            foreach ($bodies as $ks => $vs) {
                if (str_contains($vs, '文档')) {
                    ++$doc_count;
                    if ($doc_count === 1) {
                        $bodies[$ks] = '* 本次更新包含文档更新内容 {cnt} 个';
                    } else {
                        unset($bodies[$ks]);
                    }
                }
            }
            $v['body'] = implode("\r\n", $bodies);
            if ($doc_count > 0) {
                $v['body'] = str_replace('{cnt}', strval($doc_count), $v['body']);
            }
            $line .= $v['body'] . "\r\n\r\n";
        }
        // 将双空行转换为单空行
        $line = str_replace("\r\n\r\n\r\n", "\r\n\r\n", $line);

        // 转换文本换行符格式为 LF
        $line = str_replace("\r\n", "\n", $line);

        // 将所有的链接转换为可点击的链接，例如 https://example.com -> <https://example.com>
        $line = preg_replace('/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_+.~#?&\/=]*)/', '<$0>', $line);

        // 替换 PR 链接，例如 <.../pull/123> -> [PR#123](.../pull/123)
        $line = preg_replace('/<(https:\/\/github\.com\S+zhamao-framework\/pull\/(\d+))>/', '[PR#$2]($1)', $line);

        // 将 mention 转换为可点击的链接，例如 @sunxyw -> [@sunxyw](https://github.com/sunxyw)
        $line = preg_replace('/(?<=^|\s)@([\w.]+)(?<!\.)/', '[@$1](https://github.com/$1)', $line);

        // 将 Full Changelog 转换为“源码变更记录”
        $line = str_replace('Full Changelog', '源码变更记录', $line);

        if (isset($doc_count) && $doc_count > 0) {
            $line = str_replace('{cnt}', strval($doc_count), $line);
        }

        file_put_contents(FRAMEWORK_ROOT_DIR . '/docs/update/v3.md', $line);
        return static::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace ZM\Utils;

use JetBrains\PhpStorm\ArrayShape;
use ReflectionException;
use ReflectionMethod;
use ZM\Annotation\CQ\CommandArgument;
use ZM\Annotation\CQ\CQCommand;
use ZM\Console\Console;
use ZM\Event\EventManager;
use ZM\Store\WorkerCache;

class CommandInfoUtil
{
    /**
     * 判断命令信息是否已生成并缓存
     */
    public function exists(): bool
    {
        return WorkerCache::get('commands') !== null;
    }

    /**
     * 获取命令信息
     */
    #[ArrayShape([['id' => 'string', 'call' => 'callable', 'descriptions' => ['string'], 'triggers' => ['trigger_name' => ['string']], 'args' => ['arg_name' => ['name' => 'string', 'type' => 'string', 'description' => 'string', 'default' => 'mixed', 'required' => 'bool']]]])]
    public function get(): array
    {
        if (!$this->exists()) {
            return $this->generateCommandList();
        }
        return WorkerCache::get('commands');
    }

    /**
     * 重新生成命令信息
     */
    public function regenerate(): void
    {
        $this->generateCommandList();
    }

    /**
     * 获取命令帮助
     *
     * @param string $command_id 命令ID，为 `class@method` 格式
     * @param bool   $simple     是否仅输出简易信息（只有命令触发条件和描述）
     */
    public function getHelp(string $command_id, bool $simple = false): string
    {
        $command = $this->get()[$command_id];

        $formats = [
            'match' => '%s',
            'pattern' => '符合”%s“',
            'regex' => '匹配“%s”',
            'start_with' => '以”%s“开头',
            'end_with' => '以”%s“结尾',
            'keyword' => '包含“%s”',
            'alias' => '%s',
        ];
        $triggers = [];
        foreach ($command['triggers'] as $trigger => $conditions) {
            if (count($conditions) === 0) {
                continue;
            }
            if (isset($formats[$trigger])) {
                $format = $formats[$trigger];
            } else {
                Console::warning("未知的命令触发条件：{$trigger}");
                continue;
            }
            foreach ($conditions as $condition) {
                $condition = sprintf($format, $condition);
                $triggers[] = $condition;
            }
        }
        $name = array_shift($triggers);
        if (count($triggers) > 0) {
            $name .= '（' . implode('，', $triggers) . '）';
        }

        if (empty($command['descriptions'])) {
            $description = '作者很懒，啥也没说';
        } else {
            $description = implode('；', $command['descriptions']);
        }

        if ($simple) {
            return "{$name}：{$description}";
        }

        $lines = [];

        $lines[0][] = $name;
        $lines[1][] = $description;

        foreach ($command['args'] as $arg_name => $arg_info) {
            if ($arg_info['required']) {
                $lines[0][] = "<{$arg_name}: {$arg_info['type']}>";
            } else {
                $buffer = "[{$arg_name}: {$arg_info['type']}";
                if ($arg_info['default'] !== null) {
                    $buffer .= " = {$arg_info['default']}";
                }
                $lines[0][] = $buffer . ']';
            }

            $lines[1][] = "{$arg_name}；{$arg_info['description']}";
        }

        $buffer = '';
        foreach ($lines as $line) {
            $buffer .= implode(' ', $line) . "\n";
        }
        return $buffer;
    }

    /**
     * 缓存命令信息
     */
    protected function save(array $helps): void
    {
        WorkerCache::set('commands', $helps);
    }

    /**
     * 根据注解树生成命令信息（内部）
     */
    protected function generateCommandList(): array
    {
        $commands = [];

        foreach (EventManager::$events[CQCommand::class] as $annotation) {
            // 正常来说不可能，但保险起见需要判断
            if (!$annotation instanceof CQCommand) {
                continue;
            }

            $id = "{$annotation->class}@{$annotation->method}";

            try {
                $reflection = new ReflectionMethod($annotation->class, $annotation->method);
            } catch (ReflectionException $e) {
                Console::warning('命令 ' . $id . ' 注解解析错误：' . $e->getMessage());
                continue;
            }

            $doc = $reflection->getDocComment();
            if ($doc) {
                // 匹配出不以@开头，且后接中文或任意非空格字符，并以换行符结尾的字符串，也就是命令描述
                preg_match_all('/\*\s((?!@)[\x{4e00}-\x{9fa5}\S]+)(\r\n|\r|\n)/u', $doc, $descriptions);
                $descriptions = $descriptions[1];
            }

            $command = [
                'id' => $id,
                'call' => [$annotation->class, $annotation->method],
                'descriptions' => $descriptions ?? [],
                'triggers' => [],
                'args' => [],
            ];

            if (empty($command['descriptions'])) {
                Console::warning("命令没有描述信息：{$id}");
            }

            // 可能的触发条件，顺序会影响命令帮助的生成结果
            $possible_triggers = ['match', 'pattern', 'regex', 'start_with', 'end_with', 'keyword', 'alias'];
            foreach ($possible_triggers as $trigger) {
                if (isset($annotation->{$trigger}) && !empty($annotation->{$trigger})) {
                    // 部分触发条件可能存在多个
                    if (is_iterable($annotation->{$trigger})) {
                        foreach ($annotation->{$trigger} as $item) {
                            $command['triggers'][$trigger][] = $item;
                        }
                    } else {
                        $command['triggers'][$trigger][] = $annotation->{$trigger};
                    }
                }
            }
            if (empty($command['triggers'])) {
                Console::warning("命令没有触发条件：{$id}");
                continue;
            }

            $command['args'] = $this->generateCommandArgumentList($id);

            $commands[$id] = $command;
        }

        $this->save($commands);
        return $commands;
    }

    /**
     * 生成指定命令的参数列表
     *
     * @param string $id 命令 ID
     */
    protected function generateCommandArgumentList(string $id): array
    {
        [$class, $method] = explode('@', $id);
        $map = EventManager::$event_map[$class][$method];

        $args = [];

        foreach ($map as $annotation) {
            if (!$annotation instanceof CommandArgument) {
                continue;
            }

            $args[$annotation->name] = [
                'name' => $annotation->name,
                'type' => $annotation->type,
                'description' => $annotation->description,
                'default' => $annotation->default,
                'required' => $annotation->required,
            ];
        }

        return $args;
    }
}

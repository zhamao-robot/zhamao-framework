<?php

declare(strict_types=1);

namespace ZM\Plugin\CommandManual;

use ZM\Annotation\OneBot\BotCommand;
use ZM\Annotation\OneBot\CommandArgument;
use ZM\Annotation\OneBot\CommandHelp;

class StaticManualFactory
{
    public function __construct()
    {
    }

    public function __invoke(BotCommand $command, array $template, array $adjacent_annotations): string
    {
        // 在邻近注解中寻找 CommandHelp 注解
        foreach ($adjacent_annotations as $annotation) {
            if ($annotation instanceof CommandHelp) {
                $help = $annotation;
                break;
            }
        }
        $help = $help ?? new CommandHelp('', '', '');

        // 逐步构建帮助文本
        $section = '';
        foreach ($template as $v) {
            $content = $this->getSectionContent($command, $v['type'], $help);
            $this->addSection($section, $content, $v);
        }
        return $section;
    }

    private function getSectionContent(BotCommand $command, string $type, CommandHelp $help): string
    {
        switch ($type) {
            case 'command':
                return $command->name;
            case 'description':
                return $help->description;
            case 'usage':
                return $help->usage;
            case 'arguments':
                $ret = '';
                foreach ($command->getArguments() as $argument) {
                    /* @var CommandArgument $argument */
                    $ret .= $argument->name . ' - ' . $argument->description . PHP_EOL;
                }
                return $ret;
            case 'examples':
                return $help->example;
            default:
                return '';
        }
    }

    private function addSection(string &$section, string $content, array $options): void
    {
        if (!$content) {
            return;
        }
        if ($options['header']) {
            $section .= $options['header'] . PHP_EOL;
        }
        if ($options['indent']) {
            $content = '    ' . str_replace(PHP_EOL, PHP_EOL . '    ', $content);
            $content = rtrim($content);
        }
        $section .= $content . PHP_EOL;
    }
}

<?php

declare(strict_types=1);

namespace ZM\Command\Generate;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use ZM\Command\Command;

#[AsCommand(name: 'generate:text', description: '生成一些文本（内部）')]
class TextGenerateCommand extends Command
{
    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, '生成的文本内容');
    }

    /**
     * {@inheritDoc}
     */
    protected function handle(): int
    {
        return match ($this->input->getArgument('name')) {
            'class-alias-md' => $this->generateClassAliasDoc(),
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
        $this->write('| ' . str_pad('全类名', $full_maxlen) . ' | ' . str_pad('别名', $short_maxlen) . ' |');
        $this->write('| ' . str_pad('', $full_maxlen, '-') . ' | ' . str_pad('', $short_maxlen, '-') . ' |');
        foreach ($line as $v) {
            $this->write('| ' . str_pad('`' . $v[0] . '`', $full_maxlen) . ' | ' . str_pad('`' . $v[1] . '`', $short_maxlen) . ' |');
        }
        return static::SUCCESS;
    }
}

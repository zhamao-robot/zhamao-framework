<?php

declare(strict_types=1);

namespace ZM\Command\Generate;

use Symfony\Component\Console\Attribute\AsCommand;
use ZM\Command\Command;
use ZM\Container\ClassAliasHelper;

#[AsCommand(name: 'generate:alias-helper', description: '类别名的 IDE Helper 文件生成')]
class ClassAliasHelperGenerateCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function handle(): int
    {
        $str = "<?php\n\ndeclare(strict_types=1);\n\n";
        $alias = ClassAliasHelper::getAllAlias();
        foreach ($alias as $a => $c) {
            $str .= "class_alias({$c['class']}::class, '{$a}');\n";
        }
        file_put_contents(FRAMEWORK_ROOT_DIR . '/src/Globals/global_class_alias_helper.php', $str);
        $this->info('生成成功');
        return Command::SUCCESS;
    }
}

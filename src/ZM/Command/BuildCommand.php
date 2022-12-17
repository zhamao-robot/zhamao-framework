<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'build', description: '将项目构建一个phar包')]
class BuildCommand extends Command
{
    /**
     * 配置
     */
    protected function configure()
    {
        $this->setHelp('此功能将会把整个项目打包为phar');
        $this->addOption('target', 'D', InputOption::VALUE_REQUIRED, 'Output Directory | 指定输出目录');
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /* TODO
        $this->output = $output;
        $target_dir = $input->getOption('target') ?? WORKING_DIR;
        if (mb_strpos($target_dir, '../')) {
            $target_dir = realpath($target_dir);
        }
        if ($target_dir === false) {
            $output->writeln(TermColor::color8(31) . zm_internal_errcode('E00039') . 'Error: No such file or directory (' . $target_dir . ')' . TermColor::RESET);
            return 1;
        }
        $output->writeln('Target: ' . $target_dir);
        if (mb_substr($target_dir, -1, 1) !== '/') {
            $target_dir .= '/';
        }
        if (ini_get('phar.readonly') == 1) {
            $output->writeln(TermColor::color8(31) . zm_internal_errcode('E00040') . 'You need to set "phar.readonly" to "Off"!');
            $output->writeln(TermColor::color8(31) . 'See: https://stackoverflow.com/questions/34667606/cant-enable-phar-writing');
            return 1;
        }
        if (!is_dir($target_dir)) {
            $output->writeln(TermColor::color8(31) . zm_internal_errcode('E00039') . "Error: No such file or directory ({$target_dir})" . TermColor::RESET);
            return 1;
        }
        $filename = 'server.phar';
        $this->build($target_dir, $filename);
        */
        $output->writeln('<error>Not implemented.</error>');
        return 1;
    }
    /*
        private function build($target_dir, $filename)
        {
            @unlink($target_dir . $filename);
            $phar = new Phar($target_dir . $filename);
            $phar->startBuffering();

            $all = DataProvider::scanDirFiles(DataProvider::getSourceRootDir(), true, true);

            $all = array_filter($all, function ($x) {
                $dirs = preg_match('/(^(bin|config|resources|src|vendor)\\/|^(composer\\.json|README\\.md)$)/', $x);
                return !($dirs !== 1);
            });

            sort($all);

            $archive_dir = DataProvider::getSourceRootDir();
            $map = [];

            if (class_exists('\\League\\CLImate\\CLImate')) {
                $climate = new CLImate();
                $progress = $climate->progress()->total(count($all));
            }
            foreach ($all as $k => $v) {
                $map[$v] = $archive_dir . '/' . $v;
                if (isset($progress)) {
                    $progress->current($k + 1, 'Adding ' . $v);
                }
            }
            $this->output->write('<info>Building...</info>');
            $phar->buildFromIterator(new ArrayIterator($map));
            $phar->setStub(
                "#!/usr/bin/env php\n" .
                $phar->createDefaultStub(LOAD_MODE == 0 ? 'src/entry.php' : 'vendor/zhamao/framework/src/entry.php')
            );
            $phar->stopBuffering();
            $this->output->writeln('');
            $this->output->writeln('Successfully built. Location: ' . $target_dir . "{$filename}");
            $this->output->writeln('<info>You may use `chmod +x server.phar` to let phar executable with `./` command</info>');
        }
    */
}

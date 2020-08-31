<?php


namespace ZM;


use Exception;
use ZM\Command\BuildCommand;
use ZM\Command\InitCommand;
use ZM\Command\RunServerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleApplication extends Application
{
    public function __construct(string $name = 'UNKNOWN') {
        $version = json_decode(file_get_contents(__DIR__ . "/../../composer.json"), true)["version"] ?? "UNKNOWN";
        parent::__construct($name, $version);
    }

    public function initEnv() {
        $this->addCommands([
            new RunServerCommand(), //运行主服务的指令控制器
            new InitCommand() //初始化用的，用于项目初始化和phar初始化
        ]);
        if (LOAD_MODE === 0) $this->add(new BuildCommand()); //只有在git源码模式才能使用打包指令
    }

    /**
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     * @return int
     */
    public function run(InputInterface $input = null, OutputInterface $output = null) {
        try {
            return parent::run($input, $output);
        } catch (Exception $e) {
            die("{$e->getMessage()} at {$e->getFile()}({$e->getLine()})");
        }
    }
}

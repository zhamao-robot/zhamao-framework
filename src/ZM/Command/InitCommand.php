<?php


namespace ZM\Command;

use Phar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    private $extract_files = [
        "/config/global.php",
        "/.gitignore",
        "/config/file_header.json",
        "/config/console_color.json",
        "/config/motd.txt",
        "/src/Module/Example/Hello.php",
        "/src/Module/Middleware/TimerMiddleware.php",
        "/src/Custom/global_function.php"
    ];

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'init';

    protected function configure() {
        $this->setDescription("Initialize framework starter | 初始化框架运行的基础文件");
        $this->setDefinition([
            new InputOption("force", "F", null, "强制重制，覆盖现有文件")
        ]);
        $this->setHelp("此命令将会解压以下文件到项目的根目录：\n" . implode("\n", $this->getExtractFiles()));
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        if (LOAD_MODE === 1) { // 从composer依赖而来的项目模式，最基本的需要初始化的模式
            $output->writeln("<comment>Initializing files</comment>");
            $base_path = LOAD_MODE_COMPOSER_PATH;
            $args = $input->getArgument("force");
            foreach ($this->extract_files as $file) {
                if (!file_exists($base_path . $file) || $args) {
                    $info = pathinfo($file);
                    @mkdir($base_path . $info["dirname"], 0777, true);
                    echo "Copying " . $file . PHP_EOL;
                    $package_name = ($version = json_decode(file_get_contents(__DIR__ . "/../../../composer.json"), true)["name"]);
                    copy($base_path . "/vendor/" . $package_name . $file, $base_path . $file);
                } else {
                    echo "Skipping " . $file . " , file exists." . PHP_EOL;
                }
            }
            $autoload = [
                "psr-4" => [
                    "Module\\" => "src/Module",
                    "Custom\\" => "src/Custom"
                ],
                "files" => [
                    "src/Custom/global_function.php"
                ]
            ];
            if (file_exists($base_path . "/composer.json")) {
                $composer = json_decode(file_get_contents($base_path . "/composer.json"), true);
                if (!isset($composer["autoload"])) {
                    $composer["autoload"] = $autoload;
                } else {
                    foreach ($autoload["psr-4"] as $k => $v) {
                        if (!isset($composer["autoload"]["psr-4"][$k])) $composer["autoload"]["psr-4"][$k] = $v;
                    }
                    foreach ($autoload["files"] as $k => $v) {
                        if (!in_array($v, $composer["autoload"]["files"])) $composer["autoload"]["files"][] = $v;
                    }
                }
                file_put_contents($base_path . "/composer.json", json_encode($composer, 64 | 128 | 256));
                $output->writeln("<info>Executing composer command: `composer dump-autoload`</info>");
                exec("composer dump-autoload");
                echo PHP_EOL;
            } else {
                echo("Error occurred. Please check your updates.\n");
                return Command::FAILURE;
            }
            $output->writeln("<info>Done!</info>");
            return Command::SUCCESS;
        } elseif (LOAD_MODE === 2) { //从phar启动的框架包，初始化的模式
            $phar_link = new Phar(__DIR__);
            $current_dir = pathinfo($phar_link->getPath())["dirname"];
            chdir($current_dir);
            $phar_link = "phar://" . $phar_link->getPath();
            foreach ($this->extract_files as $file) {
                if (!file_exists($current_dir . $file)) {
                    $info = pathinfo($file);
                    @mkdir($current_dir . $info["dirname"], 0777, true);
                    echo "Copying " . $file . PHP_EOL;
                    file_put_contents($current_dir . $file, file_get_contents($phar_link . $file));
                } else {
                    echo "Skipping " . $file . " , file exists." . PHP_EOL;
                }
            }
        }
        $output->writeln("initialization must be started with composer-project mode!");
        return Command::FAILURE;
    }

    private function getExtractFiles(): array {
        return $this->extract_files;
    }
}

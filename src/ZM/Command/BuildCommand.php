<?php


namespace ZM\Command;

use Phar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Console\TermColor;

class BuildCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'build';
    /**
     * @var OutputInterface
     */
    private $output = null;

    protected function configure() {
        $this->setDescription("Build an \".phar\" file | 将项目构建一个phar包");
        $this->setHelp("此功能将会把炸毛框架的模块打包为\".phar\"，供发布和执行。");
        $this->addOption("target", "D", InputOption::VALUE_REQUIRED, "Output Directory | 指定输出目录");
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->output = $output;
        $target_dir = $input->getOption("target") ?? (__DIR__ . '/../../../resources/');
        if (mb_strpos($target_dir, "../")) $target_dir = realpath($target_dir);
        if ($target_dir === false) {
            $output->writeln(TermColor::color8(31) . "Error: No such file or directory (" . __DIR__ . '/../../../resources/' . ")" . TermColor::RESET);
            return Command::FAILURE;
        }
        $output->writeln("Target: " . $target_dir . " , Version: " . ($version = json_decode(file_get_contents(__DIR__ . "/../../../composer.json"), true)["version"]));
        if (mb_substr($target_dir, -1, 1) !== '/') $target_dir .= "/";
        if (ini_get('phar.readonly') == 1) {
            $output->writeln(TermColor::color8(31) . "You need to set \"phar.readonly\" to \"Off\"!");
            $output->writeln(TermColor::color8(31) . "See: https://stackoverflow.com/questions/34667606/cant-enable-phar-writing");
            return Command::FAILURE;
        }
        if (!is_dir($target_dir)) {
            $output->writeln(TermColor::color8(31) . "Error: No such file or directory ($target_dir)" . TermColor::RESET);
            return Command::FAILURE;
        }
        $filename = "server.phar";
        $this->build($target_dir, $filename);

        return Command::SUCCESS;
    }

    private function build($target_dir, $filename) {
        @unlink($target_dir . $filename);
        $phar = new Phar($target_dir . $filename);
        $phar->startBuffering();
        $src = realpath(__DIR__ . '/../../zhamao-framework/');
        $hello = file_get_contents($src . '/src/Module/Example/Hello.php');
        $middleware = file_get_contents($src . '/src/Module/Middleware/TimerMiddleware.php');
        unlink($src . '/src/Module/Example/Hello.php');
        unlink($src . '/src/Module/Middleware/TimerMiddleware.php');
        $phar->buildFromDirectory($src);
        $phar->addFromString('tmp/Hello.php.bak', $hello);
        $phar->addFromString('tmp/TimerMiddleware.php.bak', $middleware);
        //$phar->compressFiles(Phar::GZ);
        $phar->setStub($phar->createDefaultStub('phar-starter.php'));
        $phar->stopBuffering();
        file_put_contents($src . '/src/Module/Example/Hello.php', $hello);
        file_put_contents($src . '/src/Module/Middleware/TimerMiddleware.php', $middleware);
        $this->output->writeln("Successfully built. Location: " . $target_dir . "$filename");
    }
}

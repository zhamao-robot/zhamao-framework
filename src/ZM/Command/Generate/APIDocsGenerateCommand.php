<?php

declare(strict_types=1);

namespace ZM\Command\Generate;

use FilesystemIterator;
use Jasny\PhpdocParser\PhpdocParser;
use Jasny\PhpdocParser\Set\PhpDocumentor;
use Jasny\PhpdocParser\Tag\Summery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Utils\DataProvider;

class APIDocsGenerateCommand extends Command
{
    /**
     * @var null|string The default command name
     */
    protected static $defaultName = 'generate:api-docs';

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $warnings = [];

    /**
     * Configures the current command.
     */
    protected function configure(): void
    {
        $this->setDescription('Generate API docs');
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        // 获取源码目录的文件遍历器
        $fs = new \RecursiveDirectoryIterator(DataProvider::getSourceRootDir() . '/src/ZM', FilesystemIterator::SKIP_DOTS);

        // 初始化文档解析器
        $parser = new PhpdocParser(PhpDocumentor::tags()->with([
            new Summery(),
        ]));

        $metas = [];
        $class_count = 0;
        $method_count = 0;

        // 遍历类并将元数据添加至数组中
        foreach (new \RecursiveIteratorIterator($fs) as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();

            // 过滤不包含类的文件
            $tokens = token_get_all(file_get_contents($path));
            $found = false;
            foreach ($tokens as $token) {
                if (!is_array($token)) {
                    continue;
                }
                if ($token[0] === T_CLASS) {
                    $found = true;
                }
            }
            if (!$found) {
                continue;
            }

            // 获取完整类名
            $path = ltrim($path, DataProvider::getSourceRootDir() . '/');
            $class = str_replace(['.php', 'src/', '/'], ['', '', '\\'], $path);
            $output->writeln('正在解析类：' . $class);
            $meta = $this->getClassMetas($class, $parser);
            // 忽略不包含任何方法的类
            if (empty($meta)) {
                continue;
            }
            $metas[$class] = $meta;
            ++$class_count;
            $method_count += count($meta);
        }

        $markdown = [];
        foreach ($metas as $class => $class_metas) {
            $markdown[$class] = [];
            // 将类名作为页面大标题
            $markdown[$class]['class'] = '# ' . $class;
            foreach ($class_metas as $method => $meta) {
                $markdown[$class][$method] = $this->convertMetaToMarkdown($method, $meta);
            }
        }

        // 文档输出路径
        $docs = DataProvider::getSourceRootDir() . '/docs/api/';
        foreach ($markdown as $class => $methods) {
            $file = $docs . str_replace('\\', '/', $class) . '.md';
            // 确保目录存在
            if (!file_exists(dirname($file)) && !mkdir($concurrent_directory = dirname($file), 0777, true) && !is_dir($concurrent_directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrent_directory));
            }
            $this->output->writeln('正在生成文档：' . $file);
            $text = implode("\n\n", $methods);
            file_put_contents($file, $text);
        }

        $children = array_keys($markdown);
        $children = str_replace('\\', '/', $children);
        $class_tree = [];
        foreach ($children as $child) {
            $parent = dirname($child);
            $class_tree[$parent][] = $child;
        }
        ksort($class_tree);
        $config = 'module.exports = [';
        foreach ($class_tree as $parent => $children) {
            $encode = json_encode($this->generateSidebarConfig($parent, $children));
            $encode = str_replace('\/', '/', $encode);
            $config .= $encode . ',';
        }
        $config = rtrim($config, ',');
        $config .= ']';

        $file = DataProvider::getSourceRootDir() . '/docs/.vuepress/api.js';
        file_put_contents($file, $config);

        if (count($this->warnings)) {
            $this->output->writeln('<comment>生成过程中发现 ' . count($this->warnings) . ' 次警告</comment>');
        }
        if (count($this->errors)) {
            $output->writeln('<error>生成过程中发现错误：</error>');
            foreach ($this->errors as $error) {
                $output->writeln('<error>' . $error . '</error>');
            }
        }

        $output->writeln('<info>API 文档生成完毕</info>');
        $output->writeln(sprintf('<info>共生成 %d 个类，共 %d 个方法</info>', $class_count, $method_count));

        return self::SUCCESS;
    }

    /**
     * 获取类的元数据
     *
     * 包括类的注释、方法的注释、参数、返回值等
     */
    private function getClassMetas(string $class_name, PhpdocParser $parser): array
    {
        // 尝试获取反射类
        try {
            $class = new \ReflectionClass($class_name);
        } catch (\ReflectionException $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            return [];
        }

        // 省略注解类
        if (PHP_VERSION_ID >= 80000) {
            $doc = $class->getDocComment();
            if ($doc && strpos($doc, '@Annotation') !== false) {
                return [];
            }
        }

        $metas = [];

        // 遍历类方法
        foreach ($class->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $class_name) {
                continue;
            }

            $this->output->writeln('  正在解析方法：' . $method->getName());

            // 获取方法的注释并解析
            $doc = $method->getDocComment();
            if (!$doc) {
                $this->warning('找不到文档：' . $class_name . '::' . $method->getName());
                continue;
            }
            try {
                $meta = $parser->parse($doc);
            } catch (\Exception $e) {
                $this->error('解析失败：' . $class_name . '::' . $method->getName() . '，' . $e->getMessage());
                continue;
            }
            // 少数情况解析后会带有 */，需要去除
            array_walk_recursive($meta, static function (&$item) {
                if (is_string($item)) {
                    $item = trim(str_replace('*/', '', $item));
                }
            });

            // 对比反射方法获取的参数和注释声明的参数
            $parameters = $method->getParameters();
            $params_in_doc = $meta['params'] ?? [];

            foreach ($parameters as $parameter) {
                $parameter_name = $parameter->getName();
                // 不存在则添加进参数列表中
                if (!isset($params_in_doc[$parameter_name])) {
                    $params_in_doc[$parameter_name] = [
                        'type' => $parameter->getType()?->getName(),
                        'description' => '',
                    ];
                }
            }
            // 确保所有参数都有对应的类型和描述
            foreach ($params_in_doc as &$param) {
                if (!isset($param['type'])) {
                    $param['type'] = 'mixed';
                }
                if (!isset($param['description'])) {
                    $param['description'] = '';
                }
            }
            // 清除引用
            unset($param);
            $meta['params'] = $params_in_doc;

            // 设定方法默认返回值
            if (!isset($meta['return'])) {
                $meta['return'] = [
                    'type' => $method->getReturnType()?->getName() ?: 'mixed',
                    'description' => '',
                ];
            }

            // 设定默认描述
            if (!isset($meta['return']['description'])) {
                $meta['return']['description'] = '';
            }

            $metas[$method->getName()] = $meta;
        }

        return $metas;
    }

    /**
     * 将方法的元数据转换为 Markdown 格式
     *
     * @param string $method 方法名
     * @param array  $meta   元数据
     */
    private function convertMetaToMarkdown(string $method, array $meta): string
    {
        // 方法名作为标题
        $markdown = '## ' . $method . "\n\n";

        // 构造方法代码块
        $markdown .= '```php' . "\n";
        // TODO: 适配 private 等修饰符
        $markdown .= 'public function ' . $method . '(';
        $params = [];
        // 添加参数
        foreach ($meta['params'] as $param_name => $param_meta) {
            $params[] = sprintf('%s $%s', $param_meta['type'] ?? 'mixed', $param_name);
        }
        $markdown .= implode(', ', $params) . ')';
        // 添加返回值
        $markdown .= ': ' . $meta['return']['type'];
        $markdown .= "\n```\n\n";

        // 方法描述
        $markdown .= '### 描述' . "\n\n";
        $markdown .= ($meta['description'] ?? '作者很懒，什么也没有说') . "\n\n";

        // 参数
        if (count($meta['params'])) {
            $markdown .= '### 参数' . "\n\n";
            $markdown .= '| 名称 | 类型 | 描述 |' . "\n";
            $markdown .= '| -------- | ---- | ----------- |' . "\n";
            foreach ($meta['params'] as $param_name => $param_meta) {
                $markdown .= '| ' . $param_name . ' | ' . $param_meta['type'] . ' | ' . $param_meta['description'] . ' |' . "\n";
            }
        }

        // 返回值
        $markdown .= '### 返回' . "\n\n";
        $markdown .= '| 类型 | 描述 |' . "\n";
        $markdown .= '| ---- | ----------- |' . "\n";
        $markdown .= '| ' . $meta['return']['type'] . ' | ' . $meta['return']['description'] . ' |' . "\n";

        return $markdown;
    }

    private function generateSidebarConfig(string $title, array $items): array
    {
        return [
            'title' => $title,
            'collapsable' => true,
            'children' => $items,
        ];
    }

    private function warning(string $message): void
    {
        $this->output->writeln('<comment>' . $message . '</comment>');
        $this->warnings[] = $message;
    }

    private function error(string $message): void
    {
        $this->output->writeln('<error>' . $message . '</error>');
        $this->errors[] = $message;
    }
}

<?php

declare(strict_types=1);

namespace ZM\Annotation\OneBot;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\ErgodicAnnotation;
use ZM\Exception\InvalidArgumentException;
use ZM\Exception\ZMKnownException;

/**
 * Class CommandArgument
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ALL")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class CommandArgument extends AnnotationBase implements ErgodicAnnotation
{
    /**
     * @Required()
     */
    public string $name;

    public string $description = '';

    public string $type = 'string';

    public bool $required = false;

    public string $prompt = '';

    public string $default = '';

    public int $timeout = 60;

    public int $error_prompt_policy = 1;

    /**
     * @param  string                                    $name        参数名称（可以是中文）
     * @param  string                                    $description 参数描述（默认为空）
     * @param  bool                                      $required    参数是否必需，如果是必需，为true（默认为false）
     * @param  string                                    $prompt      当参数为必需时，返回给用户的提示输入的消息（默认为"请输入$name"）
     * @param  string                                    $default     当required为false时，未匹配到参数将自动使用default值（默认为空）
     * @param  int                                       $timeout     prompt超时时间（默认为60秒）
     * @throws InvalidArgumentException|ZMKnownException
     */
    public function __construct(
        string $name,
        string $description = '',
        string $type = 'string',
        bool $required = false,
        string $prompt = '',
        string $default = '',
        int $timeout = 60,
        int $error_prompt_policy = 1
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->type = $this->fixTypeName($type);
        $this->required = $required;
        $this->prompt = $prompt;
        $this->default = $default;
        $this->timeout = $timeout;
        $this->error_prompt_policy = $error_prompt_policy;
        if ($this->type === 'bool') {
            if ($this->default === '') {
                $this->default = 'yes';
            }
            if (!in_array($this->default, array_merge(TRUE_LIST, FALSE_LIST))) {
                throw new InvalidArgumentException('CommandArgument参数 ' . $name . ' 类型传入类型应为布尔型，检测到非法的默认值 ' . $this->default);
            }
        } elseif ($this->type === 'number') {
            if ($this->default === '') {
                $this->default = '0';
            }
            if (!is_numeric($this->default)) {
                throw new InvalidArgumentException('CommandArgument参数 ' . $name . ' 类型传入类型应为数字型，检测到非法的默认值 ' . $this->default);
            }
        }
    }

    public function getTypeErrorPrompt(): string
    {
        return '参数类型错误，请重新输入！';
    }

    public function getErrorQuitPrompt(): string
    {
        return '参数类型错误，停止输入！';
    }

    /**
     * @throws ZMKnownException
     */
    protected function fixTypeName(string $type): string
    {
        $table = [
            'str' => 'string',
            'string' => 'string',
            'strings' => 'string',
            'byte' => 'string',
            'num' => 'number',
            'number' => 'number',
            'int' => 'number',
            'float' => 'number',
            'double' => 'number',
            'boolean' => 'bool',
            'bool' => 'bool',
            'true' => 'bool',
            'any' => 'any',
            'all' => 'any',
            '*' => 'any',
        ];
        if (array_key_exists($type, $table)) {
            return $table[$type];
        }
        throw new ZMKnownException(zm_internal_errcode('E00077') . 'Invalid argument type: ' . $type . ', only support any, string, number and bool !');
    }
}

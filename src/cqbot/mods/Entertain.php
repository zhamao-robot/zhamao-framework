<?php

/**
 * 这是一个示例模块php文件，你可以直接复制此文件中的代码
 * 然后修改其class的名字（注意要和.php文件的文件名相同）
 * 例如，新建一个Mailer模块，则Mailer模块的文件名字为
 * Mailer.php
 * 然后更改class Entertain为class Mailer即可
 * 功能在execute中编写即可
 * 如果需要写判断所有文本的功能，则直接在__construct的parent::__construct下方编写自己的内容即可
 */
class Entertain extends ModBase
{
    public function __construct(CQBot $main, $data, bool $mod_cmd = false) { parent::__construct($main, $data, $mod_cmd); }

    public function execute($it) {
        switch ($it[0]) {
            case "你好":
                $this->reply("你好，我是CQBot！");
                return true;
            case "robot":
                $this->reply("机器人！\n机器人！");
                return true;
        }
        return false;
    }
}
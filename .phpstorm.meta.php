<?php

namespace PHPSTORM_META {

    use OneBot\V12\Object\Event\OneBotEvent;
    use ZM\Context\Context;

    override(Context::__call(0), map([
        'getBotEvent' => OneBotEvent::class,
    ]));
}

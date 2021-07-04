<?php


namespace ZMTest\Testing;


use Doctrine\Common\Annotations\AnnotationException;
use Module\Example\Hello;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Swoole\Atomic;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\CQ\CQCommand;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Event\EventManager;
use ZM\Store\LightCacheInside;
use ZM\Store\ZMAtomic;

class EventDispatcherTest extends TestCase
{

    public function testDispatch() {
        Console::init(2);
        if (!defined("WORKING_DIR"))
            define("WORKING_DIR", realpath(__DIR__ . "/../../../"));
        if (!defined("LOAD_MODE"))
            define("LOAD_MODE", 0);
        Console::init(4);
        ZMAtomic::$atomics["_event_id"] = new Atomic(0);
        LightCacheInside::init();
        $parser = new AnnotationParser();
        $parser->addRegisterPath(WORKING_DIR . "/src/Module/", "Module");
        try {
            $parser->registerMods();
        } catch (ReflectionException $e) {
            throw $e;
        }
        EventManager::loadEventByParser($parser);
        $dispatcher = new EventDispatcher(CQCommand::class);
        $dispatcher->setReturnFunction(function ($result) {
            echo $result . PHP_EOL;
        });
        //$dispatcher->setRuleFunction(function ($v) { return $v->match == "qwe"; });
        $dispatcher->setRuleFunction(function ($v) { return $v->match == "你好"; });
        //$dispatcher->setRuleFunction(fn ($v) => $v->match == "qwe");
        ob_start();
        $dispatcher->dispatchEvents();
        $r = ob_get_clean();
        echo $r;
        $this->assertStringContainsString("你好啊", $r);
        $dispatcher = new EventDispatcher(CQCommand::class);
        $dispatcher->setReturnFunction(function ($result) {
            //echo $result . PHP_EOL;
        });
        //$dispatcher->setRuleFunction(function ($v) { return $v->match == "qwe"; });
        $dispatcher->setRuleFunction(function ($v) { return $v->match == "qwe"; });
        //$dispatcher->setRuleFunction(fn ($v) => $v->match == "qwe");
        $dispatcher->dispatchEvents();
    }
}

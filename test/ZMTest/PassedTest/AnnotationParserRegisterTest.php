<?php


namespace ZMTest\PassedTest;


use Exception;
use Module\Example\Hello;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Swoole\OnStart;
use ZM\Console\Console;

class AnnotationParserRegisterTest extends TestCase
{
    private $parser;

    public function setUp(): void {
        if (!defined("WORKING_DIR"))
            define("WORKING_DIR", realpath(__DIR__ . "/../../../"));
        if (!defined("LOAD_MODE"))
            define("LOAD_MODE", 0);
        Console::init(2);
        $this->parser = new AnnotationParser();
        $this->parser->addRegisterPath(WORKING_DIR . "/src/Module/", "Module");
        try {
            $this->parser->registerMods();
        } catch (ReflectionException $e) {
            throw $e;
        }
    }

    public function testAnnotation() {
        ob_start();
        $gen = $this->parser->generateAnnotationEvents();
        $m = $gen[OnStart::class][0]->method;
        $class = $gen[OnStart::class][0]->class;
        $c = new $class();
        try {
            $c->$m();
        } catch (Exception $e) {
        }
        $result = ob_get_clean();
        echo $result;
        $this->assertStringContainsString("我开始了！", $result);
    }

    public function testAnnotation2() {

        foreach ($this->parser->generateAnnotationEvents() as $k => $v) {
            foreach ($v as $vs) {
                $this->assertTrue($vs->method === null || $vs->method != '');
                $this->assertTrue(strlen($vs->class) > 0);
            }
        }
    }

    public function testAnnotationMap() {
        $map = $this->parser->getMiddlewareMap();
        $this->assertContainsEquals("timer", $map[Hello::class]["timer"]);
    }

    public function testMiddlewares() {
        $wares = $this->parser->getMiddlewares();
        $this->assertArrayHasKey("timer", $wares);
    }

    public function testReqMapping() {
        $mapping = $this->parser->getReqMapping();
        $this->assertEquals("index", $mapping["method"]);
    }
}

<?php
use Beige\Psr11\Container;
use Beige\Invoker\Invoker;

require_once __DIR__ . '/AbstractTest.php';

class Test
{

}

/**
 * @property Test $test
 */
class Test2
{
    public function __construct($test1)
    {
        $this->test1 = $test1;
    }

    public function method1($test1)
    {
        return $test1;
    }

    public function method2(Test $test, $test1)
    {
        return [$test, $test1];
    }

    public function method3(Test $test)
    {
        return $test;
    }
}

class Test3
{
    public function __construct(Test $test)
    {
        $this->test = $test;
    }
}

class InvokerTest extends AbstractTest
{
    public function testSetDefinition()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $callable = function() {
            return 1;
        };
        $invoker->setDefaultTypehintHandler($callable);
        $typehintHandler = $this->getProperty($invoker, 'typehintHandler');
        $this->assertEquals($typehintHandler, $callable);
    }

    /**
     * @expectedException \TypeError
     */
    public function testSetDefinitionWithException()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $invoker->setDefaultTypehintHandler('notCallable');
    }

    /**
     * @expectedException \Exception
     */
    public function testSetDefaultTypehintHandlerExceptionHandle()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $closure = $this->callMethod($invoker, 'typehintHandlerExceptionFactory', ['testType']);
        $closure();
    }

    public function testCallWithParams()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $func = function($test) {
            return $test;
        };
        $result = $invoker->call($func, [
            'test' => 1
        ]);
        $this->assertEquals($result, 1);
    }

    public function testCallWithoutParams()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $func = function() {
            return 1;
        };
        $result = $invoker->call($func);
        $this->assertEquals($result, 1);
    }

    public function testCallWithDefaultParamValue()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $func = function($test1 = 1, $test2) {
            return [$test1, $test2];
        };
        $result = $invoker->call($func, [
            'test2' => 2
        ]);
        $this->assertEquals($result, [1, 2]);
    }

    public function testCallWithTypehint()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $func = function(Test $test, $test1 = 1, $test2) {
            return [$test, $test1, $test2];
        };
        $t = new Test();
        $container->set(Test::class, $t);
        $result = $invoker->call($func, [
            'test2' => 2
        ]);
        $this->assertEquals($result, [$t, 1, 2]);
    }

    public function testNewWithParams()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $instance = $invoker->new(Test2::class, ['test1' => 1]);
        $this->assertInstanceOf(Test2::class, $instance);
        $result = $this->getProperty($instance, 'test1');
        $this->assertEquals(1, $result);
    }

    public function testNewWithParamsTypehint()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $t = new Test();
        $container->set(Test::class, $t);
        $instance = $invoker->new(Test3::class);
        $this->assertInstanceOf(Test3::class, $instance);
        $result = $this->getProperty($instance, 'test');
        $this->assertInstanceOf(Test::class, $result);
        $this->assertEquals($result, $t);
    }

    public function testCallMethodWithParams()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $instance = new Test2(1);
        $result = $invoker->callMethod($instance, 'method1', ['test1' => 1]);
        $this->assertEquals($result, 1);
    }

    public function testCallMethodWithTypehintParams()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $t = new Test();
        $container->set(Test::class, $t);
        $instance = new Test2(1);
        list($result1, $result2) = $invoker->callMethod($instance, 'method2', ['test1' => 1]);
        $this->assertInstanceOf(Test::class, $result1);
        $this->assertEquals($result1, $t);
        $this->assertEquals($result2, 1);
    }

    /**
     * @expectedException \Exception
     */
    public function testCallMethodWithException()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $instance = new Test2(1);
        $invoker->callMethod($instance, 'non');
    }

    public function testReflectionParametersToArgs()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $t = new Test();
        $container->set(Test::class, $t);
        $instance = new Test2(1);
        $reflectionMethod = new \ReflectionMethod($instance, 'method2');
        $parameters = $reflectionMethod->getParameters();
        $args = $this->callMethod($invoker, 'reflectionParametersToArgs', [$parameters, ['test1' => 1]]);
        $this->assertInstanceOf(Test::class, $args[0]);
        $this->assertEquals($args[1], 1);
    }

    /**
     * @expectedException \Exception
     */
    public function testReflectionParametersToArgsWithException()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $t = new Test();
        $container->set(Test::class, $t);
        $instance = new Test2(1);
        $reflectionMethod = new \ReflectionMethod($instance, 'method1');
        $parameters = $reflectionMethod->getParameters();
        $this->callMethod($invoker, 'reflectionParametersToArgs', [$parameters, ['miss' => 1]]);
    }

    public function testReflectionParametersToArgsEmpty()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $args = $this->callMethod($invoker, 'reflectionParametersToArgs', [[], []]);
        $this->assertEmpty($args);
        $this->assertEquals([], $args);
    }

    public function testTypeProcessWithCallableDefinition()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $this->setProperty($invoker, 'typehintHandler', (function($type, $throwException) {
            $this->assertInstanceOf(Closure::class, $throwException);
            $this->assertEquals(Test::class, $type);
            return new $type;
        })->bindTo($this));

        $instance = new Test2(1);
        $reflectionMethod = new \ReflectionMethod($instance, 'method3');
        $parameters = $reflectionMethod->getParameters();
        $reflectionType = $parameters[0]->getType();
        $result = $this->callMethod($invoker, 'getInstanceByName', [$reflectionType]);
        $this->assertInstanceOf(Test::class, $result);
    }

    /**
     * @expectedException \Exception
     */
    public function testTypeProcessWithException()
    {
        $container = new Container();
        $invoker = new Invoker($container);
        $instance = new Test2(1);
        $reflectionMethod = new \ReflectionMethod($instance, 'method3');
        $parameters = $reflectionMethod->getParameters();
        $reflectionType = $parameters[0]->getType();
        $this->callMethod($invoker, 'getInstanceByName', [$reflectionType]);
    }
}

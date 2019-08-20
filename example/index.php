<?php

use Beige\Invoker\Invoker;
use Beige\Psr11\Container;

require __DIR__ . '/../vendor/autoload.php';

class Test
{
    public function hello($who)
    {
        echo 'hello ', $who;
    }
}

class Invable
{
    // public function __construct(Test $test, $what)
    // {
    //     $this->test = $test;
    //     $this->what = $what;
    // }

    public function say(Test $test, $what)
    {
        $test->hello($what);
    }
}

$container = new Container([
    Test::class => new Test()
]);

$invoker = new Invoker($container);
// $invoker->call(function($a = 'haha', Test $test) {
//     $test->hello($a);
// }, ['a' => 'world']);

$instance = $invoker->new(Invable::class, ['what' => 'world']);
// $instance->say();

$instance = new Invable();
$invoker->callMethod($instance, 'say', ['what' => 'world']);

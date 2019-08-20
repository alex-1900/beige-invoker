<?php

namespace Beige\Invoker\Interfaces;

interface InvokerInterface
{
    /**
     * Calling a function or callable object.
     * Declare type parameters that will inject instances from container,
     * You can provide additional parameter values to invoker through the second parameter.
     * 
     * @param callable $invoker
     * @param array $parameters (i.e. ['param1' => 1])
     * 
     * @return mixed Return value of invoker returns.
     */
    public function call(callable $function, array $parameters = []);

    /**
     * Instantiating classes by class name.
     * Declare type parameters that will inject instances from container,
     * You can provide additional parameter values to `__construct` through the second parameter.
     * 
     * @param string $className
     * @param array $parameters (i.e. ['param1' => 1])
     * 
     * @return object Instance of class.
     */
    public function new(string $className, array $parameters = []);

    /**
     * Calling a method of instance.
     * Declare type parameters that will inject instances from container,
     * You can provide additional parameter values to method through the third parameter.
     * 
     * @param object $instance
     * @param string $method
     * @param array $parameters
     * 
     * @throws \ReflectionException
     * 
     * @return mixed The value of method.
     */
    public function callMethod(object $instance, string $method, array $parameters = []);

    /**
     * Set the undefined type-hint parameter handler
     * the instance process will inject what the $callback returns.
     * you can call the second parameter of $callback to throw processor not found exception.
     * 
     * @param callable $handler
     * 
     * @throws \InvalidArgumentException
     * 
     * @return void
     */
    public function setDefaultTypehintHandler(callable $handler);
}

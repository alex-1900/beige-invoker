<?php

namespace Beige\Invoker;

use ReflectionType;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use Psr\Container\ContainerInterface;
use Beige\Invoker\Interfaces\InvokerInterface;
use Closure;

class Invoker implements InvokerInterface
{
    /**
     * The default type-hint handler.
     * 
     * @var callback|null
     */
    private $typehintHandler = null;

    /**
     * Container.
     * 
     * @var ContainerInterface
     */
    private $container;

    /**
     * Set the container.
     * 
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

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
    public function setDefaultTypehintHandler(callable $handler)
    {
        $this->typehintHandler = $handler;
    }

    /**
     * Calling a function or callable object.
     * Declare type parameters that will inject instances from container,
     * You can provide additional parameter values to invoker through the second parameter.
     * 
     * @param mixed $function
     * @param array $parameters (i.e. ['param1' => 1])
     * 
     * @return mixed Return value of invoker
     */
    public function call(callable $function, array $parameters = [])
    {
        $reflection = new ReflectionFunction($function);
        $injectionParameters = $reflection->getParameters();
        $args = $this->reflectionParametersToArgs($injectionParameters, $parameters);
        return $reflection->invokeArgs($args);
    }

    /**
     * Instantiating classes by class name.
     * Declare type parameters that will inject instances from container,
     * You can provide additional parameter values to constructor through the second parameter.
     * 
     * @param string $className
     * @param array $parameters (i.e. ['param1' => 1])
     * 
     * @return object Instance of class.
     */
    public function new(string $className, array $parameters = [])
    {
        $args = [];
        $reflectionClass = new ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();
        if (! is_null($constructor)) {
            $injectionParameters = $constructor->getParameters();
            $args = $this->reflectionParametersToArgs($injectionParameters, $parameters);
        }
        return $reflectionClass->newInstanceArgs($args);
    }

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
    public function callMethod(object $instance, string $method, array $parameters = [])
    {
        $reflectionMethod = new ReflectionMethod($instance, $method);
        $injectionParameters = $reflectionMethod->getParameters();
        $args = $this->reflectionParametersToArgs($injectionParameters, $parameters);
        return $reflectionMethod->invokeArgs($instance, $args);
    }

    /**
     * Build arguments from reflectionParameters.
     * assignment -> defaultValue -> type-hint
     * 
     * @param ReflectionParameter[] $reflectionParameters
     * @param array $params
     * 
     * @return array
     */
    private function reflectionParametersToArgs(array $reflectionParameters, array $params)
    {
        $args = [];
        foreach ($reflectionParameters as $index => $parameter) {
            $paramName = $parameter->getName();
            if (in_array($paramName, array_keys($params))) {
                $args[$index] = $params[$paramName];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[$index] = $parameter->getDefaultValue();
            } elseif ($parameter->hasType()) {
                $typeName = $parameter->getType();
                $args[$index] = $this->getInstanceByName($typeName);
            } else {
                throw new \Exception('The parameter '. $paramName. 'has no specified type and and no assignment.');
            }
        }
        return $args;
    }

    /**
     * Type to value process.
     * 
     * @param ReflectionType $reflectionType
     * 
     * @throws \Exception
     * 
     * @return mixed
     */
    private function getInstanceByName(ReflectionType $reflectionType)
    {
        $typeName = $reflectionType->getName();
        if ($this->container->has($typeName)) {
            return $this->container->get($typeName);
        }

        if (! is_null($this->typehintHandler)) {
            $throwException = $this->typehintHandlerExceptionFactory($typeName);
            return call_user_func($this->typehintHandler, $typeName, $throwException);
        }

        throw new \Exception('There is no processor for the parameter type '. $typeName);
    }

    /**
     * The type-hint handler Exception handler.
     * 
     * @param string $typeName
     * 
     * @return Closure
     */
    private function typehintHandlerExceptionFactory(string $typeName): Closure
    {
        return function() use ($typeName) {
            throw new \Exception('There is no processor for the parameter type '. $typeName);
        };
    }
}

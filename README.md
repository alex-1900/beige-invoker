# Beige Invoker

[![GitHub license](https://img.shields.io/github/license/alienwow/SnowLeopard.svg)](https://github.com/alienwow/SnowLeopard/blob/master/LICENSE)
[![LICENSE](https://img.shields.io/badge/license-Anti%20996-blue.svg)](https://github.com/996icu/996.ICU/blob/master/LICENSE)
[![Coverage 100%](https://img.shields.io/azure-devops/coverage/swellaby/opensource/25.svg)](https://github.com/speed-sonic/beige-route)

## 基于容器技术的轻量级调用器
A lightweight invoker based on container.


## 简介
invoker 负责调用或实例化用户指定程序，并且利用 PSR-11 容器标准获取程序的依赖参数的实例。它是一个高效的，不关心参数顺序的依赖注入工具，可以给与你最大限度的灵活度和自由度。

## 安装
```
composer require beige/invoker
```

## 使用
`Beige\Invoker\Invoker` 继承于 `Beige\Invoker\Interfaces\InvokerInterface`，实现了三个标准方法：
### `Beige\Invoker\Invoker::call(callable $function[, array $parameters])`: mixed:
调用一个 `callable` 对象或函数，并注入依赖项到`callable` 对象或函数：

参数：
- `$function`: 可调用对象或函数
- `$parameters`: 如果 `$function` 对象除了依赖参数外，还有额外的参数，则可以通过这个参数以数组的形式传入。i.e. 如果参数名称为 `$foo` 则 `$parameters` 为 `['foo' => 'bar']`.

返回值：
- 返回 `$function` 的返回值

```php
use Beige\Invoker\Invoker;
use Beige\Psr11\Container;

$container = new Container([
    'Test' => ...
]);

$invoker = new Invoker($container);
$invoker->call(function(Test $test, $foo) {
    ...
}, ['foo' => 'bar']);
```
上面的例子简化了具体的代码实现，首先，`Beige\Invoker\Invoker` 的构造方法接受一个 `Psr\Container\ContainerInterface` 容器实例，我们需要将 `Test` 作为容器项索引提前实例放进容器中，`$function` 通过声明参数类型 `Test` 来获取对应的实例，这个 Test 的实例就是从容器中获取到的。

上例中的 `$function` 函数中还包含一个额外的参数 `$foo`，我们通过 Invoker 第二参数 `$parameters` 传入。`$parameters` 是一个数组，它的键必须与需要绑定的参数名称相同。实际上，`$function` 的参数不需要遵循任何顺序的束缚。

这些规律也适用于接下来的所有调用器方法。

### `Beige\Invoker\Invoker::new(string $className[, array $parameters])`: object:
实例化一个 php 类，并注入依赖项到类的构造方法。
参数：
- `$className`: 类的名称
- `$parameters`: 如果构造方法除了依赖参数外，还有额外的参数，则可以通过这个参数以数组的形式传入。i.e. 如果参数名称为 `$foo` 则 `$parameters` 为 `['foo' => 'bar']`.

返回值：
- 返回 `$className` 的实例

```php
use Beige\Invoker\Invoker;

class Invokeable
{
    public function __construct(Test $test, $foo)
    {
        ...
    }
    ...
}

$instance = $invoker->new(Invokeable::class, ['foo' => 'bar']);
```
上例与 `Invoker::call` 非常相似，只是由函数调用变成了对类的实例化操作。我们调用 `Invoker::new` 方法，从容器中取出 Test 类型的实例，并绑定到构造方法的 `$test` 参数上，并用第二个参数注入了自定义参数 `$foo`，最终得到 `Invokeable` 的实例。

### `Beige\Invoker\Invoker::callMethod(object $instance, string $method[, array $parameters])`: mixed:
调用一个对象的方法，并注入依赖项到这个方法中。

参数：
- `$instance`: 需要调用的方法所在的对象
- `$method`: 需要调用的方法名称
- `$parameters`: 如果需要调用的方法除了依赖参数外，还有额外的参数，则可以通过这个参数以数组的形式传入。i.e. 如果参数名称为 `$foo` 则 `$parameters` 为 `['foo' => 'bar']`.

返回值：
返回方法的返回值

```php
$instance = new Invokeable();

$invoker->callMethod($instance, 'someMethod', ['foo' => 'bar']);
```
与 `Invoker::new` 很相似，`Invoker::callMethod` 也会读取类型声明注入参数。

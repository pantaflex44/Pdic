<?php

/**
 * @Pdic - PHP Dependency Injection Class
 * 
 * MIT License
 * 
 * Copyright (C) 2021 Christophe LEMOINE 
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Framel\Libs\Pdic;

use Exception;
use ReflectionClass;
use ReflectionMethod;

/**
 * Dependency Injector
 */
class Pdic
{

    /**
     * Objects instance
     *
     * @var array
     */
    protected static $instances = [];

    /**
     * Set parameters
     *
     * @param string $class Class name
     * @param array $parameters Array of parameters ( [ methodName => [ key => value , ... ] , ... ] )
     * @return void
     */
    public static function setParameters(string $class, array $parameters): void
    {
        if (
            !array_key_exists(self::class, $GLOBALS)
            || !is_array($GLOBALS[self::class])
        ) {
            $GLOBALS[self::class] = [];
        }

        if (
            !array_key_exists($class, $GLOBALS[self::class])
            || !is_array($GLOBALS[self::class][$class])
        ) {
            $GLOBALS[self::class][$class] = [];
        }

        $GLOBALS[self::class][$class] += $parameters;
    }

    /**
     * Get parameters
     *
     * @param string $class Class name
     * @return array Array of parameters ( [ methodName => [ key => value , ... ] , ... ] )
     */
    public static function getParameters(string $class): array
    {
        if (
            !array_key_exists(self::class, $GLOBALS)
            || !is_array($GLOBALS[self::class])
        ) {
            return [];
        }

        if (
            !array_key_exists($class, $GLOBALS[self::class])
            || !is_array($GLOBALS[self::class][$class])
        ) {
            return [];
        }

        return $GLOBALS[self::class][$class];
    }

    /**
     * Has a definition
     *
     * @param string $class Class name
     * @return bool true, if exists, else, false
     */
    public static function hasDefinition(string $parentClass, string $methodName, string $class): bool
    {
        $classMethod = $parentClass . '@' . $methodName;

        return (array_key_exists(self::class, $GLOBALS)
            && is_array($GLOBALS[self::class])
            && array_key_exists($classMethod, $GLOBALS[self::class])
            && is_array($GLOBALS[self::class][$classMethod])
            && array_key_exists($class, $GLOBALS[self::class][$classMethod]));
    }

    /**
     * Set new definition
     *
     * @param string $class Class name
     * @return void
     */
    public static function setDefinition(string $parentClass, string $methodName, array $classes): void
    {
        $classMethod = $parentClass . '@' . $methodName;

        if (
            !array_key_exists(self::class, $GLOBALS)
            || !is_array($GLOBALS[self::class])
        ) {
            $GLOBALS[self::class] = [];
        }

        if (
            !array_key_exists($classMethod, $GLOBALS[self::class])
            || !is_array($GLOBALS[self::class][$classMethod])
        ) {
            $GLOBALS[self::class][$classMethod] = [];
        }

        $GLOBALS[self::class][$classMethod] += $classes;
    }

    /**
     * Remove a definition
     *
     * @param string $class Class name
     * @return void
     */
    public static function removeDefinition(string $parentClass, string $methodName, string $class): void
    {
        if (self::hasDefinition($parentClass, $methodName, $class)) {
            $classMethod = $parentClass . '@' . $methodName;

            unset($GLOBALS[self::class][$classMethod][$class]);
        }
    }

    /**
     * Get a definition
     *
     * @param string $class Class name
     */
    public static function getDefinition(string $parentClass, string $methodName, string $class)
    {
        if (self::hasDefinition($parentClass, $methodName, $class)) {
            $classMethod = $parentClass . '@' . $methodName;

            return $GLOBALS[self::class][$classMethod][$class];
        }

        return null;
    }

    /**
     * Resolve dependencies
     *
     * @param ReflectionMethod $method A ReflectionMethod
     * @param array $methodParams Custom parameters
     * @return array Array of method parameters with resolved dependencies
     */
    protected static function resolve(ReflectionMethod $method, array $methodParams = []): array
    {
        $getParam = function (array $prms, string $clsNm, string $mtdhNm, string $prmNm) {
            if (!array_key_exists($clsNm, $prms)) {
                throw new Exception();
            }

            if (
                !array_key_exists($mtdhNm, $prms[$clsNm])
                && !array_key_exists('*', $prms[$clsNm])
            ) {
                throw new Exception();
            }
            if (
                !array_key_exists($mtdhNm, $prms[$clsNm])
                && array_key_exists('*', $prms[$clsNm])
            ) {
                $mtdhNm = '*';
            }

            if (!array_key_exists($prmNm, $prms[$clsNm][$mtdhNm])) {
                throw new Exception();
            }

            return $prms[$clsNm][$mtdhNm][$prmNm];
        };

        if (($params = $method->getParameters()) === []) {
            return [];
        }

        $className = $method->getDeclaringClass()->getName();
        $methodName = $method->getName();

        $methodParams += [$className => self::getParameters($className)];

        $nip = [];
        foreach ($params as $param) {
            $cls = $param->getType() && !$param->getType()->isBuiltin()
                ? new ReflectionClass($param->getType()->getName())
                : null;

            if ($cls === null) {
                try {
                    $nip[] = $getParam($methodParams, $className, $methodName, $param->getName());
                    continue;
                } catch (Exception $ex) {
                    if (!$param->isDefaultValueAvailable()) {
                        throw new Exception(sprintf('Unknown param `%s`', $param->getName()));
                    }

                    $nip[] = $param->getDefaultValue();
                    continue;
                }

                $nip[] = $methodParams[$className][$methodName];
                continue;
            }

            $pType = $param->getType()->getName();

            try {
                $nip[] = $getParam($methodParams, $className, $methodName, $pType);
                continue;
            } catch (Exception $ex) {
                $exit = false;

                foreach ([$className, '*'] as $_className) {
                    foreach ([$methodName, '*'] as $_methodName) {
                        if (self::hasDefinition($_className, $_methodName, $pType)) {
                            $nip[] = self::getDefinition($_className, $_methodName, $pType);

                            $exit = true;
                            break;
                        }
                    }
                    if ($exit) {
                        break;
                    }
                }

                if ($exit) {
                    continue;
                }
            }

            $nip[] = self::get($cls->getName(), $methodParams);
        }

        return $nip;
    }

    /**
     * Get object constructor
     *
     * @param ReflectionClass $rc ReflectionClass
     * @return ReflectionMethod|null Constructor or null
     */
    protected static function getCTor(ReflectionClass $rc): ?ReflectionMethod
    {
        if (!$rc->isInstantiable()) {
            throw new Exception(sprintf('`%s` not instanciable.', $rc->getName()));
        }

        return $rc->getConstructor();
    }

    /**
     * Instanciate an object
     *
     * @param object|string $classOrObject Class name or object instance
     * @param array $methodParams Custum parameters
     * @return object|null Instance or null
     */
    protected static function instanciate(object|string $classOrObject, array $methodParams = []): ?object
    {
        $rc = new ReflectionClass($classOrObject);

        if (($ctor = self::getCTor($rc)) === null) {
            return $rc->newInstance();
        }

        $class = $rc->getName();
        $methodParams += [$class => self::getParameters($class)];

        if (($params = self::resolve($ctor, $methodParams)) === []) {
            return $rc->newInstance();
        }

        return $rc->newInstanceArgs($params);
    }

    /**
     * Invoke a class method
     *
     * @param string $class Class that contains method
     * @param string $methodName Method name from class
     * @param array $methodParams Custom params
     */
    public static function invoke(string $class, string $methodName, array $methodParams = [])
    {
        if (($rm = new ReflectionMethod($class, $methodName)) === null) {
            return null;
        }

        $methodParams += [$class => self::getParameters($class)];

        if (($params = self::resolve($rm, $methodParams)) === []) {
            return $rm->invoke(self::get($class, $methodParams));
        }

        return $rm->invokeArgs(self::get($class, $methodParams), $params);
    }

    /**
     * Get an object instance with dependencies
     *
     * @param string $class Object class name
     * @return object|null The object instance or null
     */
    public static function get(string $class, array $methodParams = []): ?object
    {
        $methodParams += [$class => self::getParameters($class)];

        if (!array_key_exists($class, self::$instances)) {
            self::$instances[$class] = self::instanciate($class, $methodParams);
        }

        if (count($methodParams) > 0) {
            self::$instances[$class] = self::instanciate(self::$instances[$class], $methodParams);
        }

        return self::$instances[$class];
    }

    /**
     * Has a dependency
     *
     * @param string $class
     * @return boolean true, Di can access or known a class, else, false
     */
    public static function isKnown(string $class): bool
    {
        if (array_key_exists($class, self::$instances)) {
            return true;
        }

        return class_exists($class, false);
    }

    /**
     * Set an object instance
     *
     * @param string $alias Alias name
     * @param string $instance Object instance
     * @return void
     */
    public static function set(object $instance): void
    {
        $class = $instance::class;
        self::$instances[$class] = $instance;
    }
}

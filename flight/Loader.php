<?php

declare(strict_types=1);
/**
 * Flight: An extensible micro-framework.
 *
 * @copyright   Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 * @license     MIT, http://flightphp.com/license
 */

namespace flight;

use Exception;
use ReflectionClass;
use ReflectionException;

/**
 * The Loader class is responsible for loading objects. It maintains
 * a list of reusable class instances and can generate a new class
 * instances with custom initialization parameters. It also performs
 * class autoloading.
 */
class Loader
{
    /**
     * Registered classes.
     */
    protected array $classes = [];

    /**
     * Class instances.
     */
    protected array $instances = [];

    /**
     * Autoload directories.
     */
    protected static array $dirs = [];

    /**
     * Registers a class.
     *
     * @param string          $name     Registry name
     * @param callable|string $class    Class name or function to instantiate class
     * @param array           $params   Class initialization parameters
     * @param callable|null   $callback $callback Function to call after object instantiation
     */
    public function register(string $name, $class, array $params = [], ?callable $callback = null): void
    {
        unset($this->instances[$name]);
        $this->classes[$name] = [$class, $params, $callback];
    }

    /**
     * Unregisters a class.
     *
     * @param string $name Registry name
     */
    public function unregister(string $name): void
    {
        unset($this->classes[$name]);
    }

    /**
     * Loads a registered class.
     *
     * @param string $name   Method name
     * @param bool   $shared Shared instance
     *
     * @throws Exception
     *
     * @return object Class instance
     */
    public function load(string $name, bool $shared = true): ?object
    {
        if (!isset($this->classes[$name])) return null;
    
        [$class, $params, $callback] = $this->classes[$name];

        if ($shared && isset($this->instances[$name])) {
            return $this->getInstance($name);
        }
    
        $obj = $this->newInstance($class, $params);
    
        if ($shared) {
            $this->instances[$name] = $obj;
        }
    
        if ($callback && (!$shared || !isset($this->instances[$name]))) {
            \call_user_func($callback, $obj);
        }
    
        return $obj;
    }

    /**
     * Gets a single instance of a class.
     *
     * @param string $name Instance name
     *
     * @return object Class instance
     */
    public function getInstance(string $name): ?object
    {
        return $this->instances[$name] ?? null;
    }

    /**
     * Gets a new instance of a class.
     *
     * @param callable|string $class  Class name or callback function to instantiate class
     * @param array           $params Class initialization parameters
     *
     * @throws Exception
     *
     * @return object Class instance
     */
    public function newInstance($class, array $params = []): object
    {
        if (\is_callable($class)) {
            return \call_user_func_array($class, $params);
        }
        
        try {
            $refClass = new ReflectionClass($class);
            return $refClass->newInstanceArgs($params);
        } catch (ReflectionException $e) {
            throw new Exception("Cannot instantiate {$class}", 0, $e);
        }

    }

    /**
     * @param string $name Registry name
     *
     * @return mixed Class information or null if not registered
     */
    public function get(string $name)
    {
        return $this->classes[$name] ?? null;
    }

    /**
     * Resets the object to the initial state.
     */
    public function reset(): void
    {
        $this->classes = [];
        $this->instances = [];
    }

    // Autoloading Functions

    /**
     * Starts/stops autoloader.
     *
     * @param bool  $enabled Enable/disable autoloading
     * @param mixed $dirs    Autoload directories
     */
    public static function autoload(bool $enabled = true, $dirs = []): void
    {
        $autoloadFunction = 'spl_autoload_' . ($enabled ? 'register' : 'unregister');
        $autoloadFunction([__CLASS__, 'loadClass']);
    
        self::addDirectory($dirs);
    }

    /**
     * Autoloads classes.
     *
     * @param string $class Class name
     */
    public static function loadClass(string $class): void
    {
        $classFile = str_replace(['\\', '_'], '/', $class) . '.php';

        foreach (self::$dirs as $dir) {
            $file = $dir . '/' . $classFile;
            if (is_readable($file)) {
                require $file;
                return;
            }
        }
    }

    /**
     * Adds a directory for autoloading classes.
     *
     * @param mixed $dir Directory path
     */
    public static function addDirectory($dir): void
    {
        if (\is_array($dir) || \is_object($dir)) {
            $directories = \is_array($dir) ? $dir : \iterator_to_array($dir);
            self::$dirs = \array_merge(self::$dirs, \array_unique($directories));
        } elseif (\is_string($dir) && !\in_array($dir, self::$dirs, true)) {
            self::$dirs[] = $dir;
        }
    }
}

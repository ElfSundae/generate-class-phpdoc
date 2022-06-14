<?php

namespace Elfsundae\Laravel;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

class FacadePhpdocGenerator
{
    protected $reflections = [];

    protected $modifier = ReflectionMethod::IS_PUBLIC;
    protected $excluded = [];
    protected $filter;
    protected $add = [];
    protected $see = [];

    /**
     * Create a new generator instance.
     *
     * @param string|object|array $classes
     *
     * @throws \ReflectionException
     */
    public function __construct($classes)
    {
        $classes = is_array($classes) ? $classes : func_get_args();
        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $this->reflections[$reflection->getName()] = $reflection;
        }

        $this->filter = static::defaultFilter();
    }

    /**
     * Create a new generator instance.
     *
     * @param string|object|array $classes
     * @return static
     *
     * @throws \ReflectionException
     */
    public static function make($classes)
    {
        return new static(is_array($classes) ? $classes : func_get_args());
    }

    /**
     * Set the method modifier to filter the PHPDocs to include only methods
     * with certain attributes. Defaults to ReflectionMethod::IS_PUBLIC.
     *
     * @param int $modifier Bitwise of ReflectionMethod modifiers.
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function modifier($modifier)
    {
        if (! is_int($modifier)) {
            throw new InvalidArgumentException('$modifier should be int');
        }

        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Exclude some methods by names.
     *
     * @param string|array $names
     * @return $this
     */
    public function exclude($names)
    {
        $names = is_array($names) ? $names : func_get_args();
        $this->excluded = array_merge($this->excluded, $names);

        return $this;
    }

    /**
     * Set the method filter callback. Defaults to `FacadePhpdocGenerator::defaultFilter()`.
     *
     * @param (callable(ReflectionMethod): bool)|null $filter
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function filter($filter)
    {
        if (! is_callable($filter) && ! is_null($filter)) {
            throw new InvalidArgumentException('$filter should be callable');
        }

        $this->filter = $filter;

        return $this;
    }

    /**
     * The default filter that excludes methods which name begins with '__'.
     *
     * @return callable
     */
    public static function defaultFilter()
    {
        return static function ($method) {
            return strpos($method->getName(), '__') !== 0;
        };
    }

    /**
     * Add method PHPDoc comments.
     *
     * @param string|array $doc
     * @param string $position
     * @param string $name
     * @return $this
     */
    public function add($doc, $position = '', $name = '')
    {
        $doc = is_array($doc) ? array_values($doc) : [$doc];
        $key = $position.'_'.$name;
        $this->add[$key] = ! isset($this->add[$key]) ? $doc : array_merge($this->add[$key], $doc);

        return $this;
    }

    /**
     * Add method PHPDoc comments before the given method name.
     *
     * @param string|array $doc
     * @param string $name
     * @return $this
     */
    public function addBefore($doc, $name)
    {
        return $this->add($doc, 'before', $name);
    }

    /**
     * Add method PHPDoc comments after the given method name.
     *
     * @param string|array $doc
     * @param string $name
     * @return $this
     */
    public function addAfter($doc, $name)
    {
        return $this->add($doc, 'after', $name);
    }

    /**
     * Set the "@see" classes.
     *
     * @param string|array $classes
     * @return $this
     */
    public function see($classes)
    {
        $this->see = is_array($classes) ? $classes : func_get_args();

        return $this;
    }

    /**
     * Generate the PHPDoc comments.
     *
     * @return string
     */
    public function generate()
    {
        $doc = '/**'.PHP_EOL;

        foreach ($this->getMethods() as $method) {
            $doc .= ' * @method static '.$method.PHP_EOL;
        }
        $doc .= ' *'.PHP_EOL;

        foreach ($this->see ?: array_keys($this->reflections) as $class) {
            $doc .= ' * @see \\'.$class.PHP_EOL;
        }

        $doc .= ' */'.PHP_EOL;

        return $doc;
    }

    /**
     * Generate the PHPDoc comments.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->generate();
    }

    /**
     * Return methods PHPDocs as an array.
     *
     * @return array
     */
    public function getMethods()
    {
        $docs = [];
        $gotNames = [];
        foreach ($this->reflections as $reflection) {
            foreach ($reflection->getMethods($this->modifier) as $method) {
                $name = $method->getName();
                if (in_array($name, $gotNames)) {
                    continue;
                }
                if ($this->excluded && in_array($name, $this->excluded)) {
                    continue;
                }
                if ($this->filter && ! call_user_func($this->filter, $method)) {
                    continue;
                }

                if (isset($this->add['before_'.$name])) {
                    $docs = array_merge($docs, $this->add['before_'.$name]);
                }
                $docs[] = $this->getReturnType($method).$name.$this->getParameters($method);
                if (isset($this->add['after_'.$name])) {
                    $docs = array_merge($docs, $this->add['after_'.$name]);
                }
            }
        }

        if (isset($this->add['_'])) {
            $docs = array_merge($docs, $this->add['_']);
        }

        return array_values(array_unique($docs));
    }

    /**
     * Get the method's return type.
     *
     * @param ReflectionMethod $method
     * @return string
     */
    protected function getReturnType($method)
    {
        $type = method_exists($method, 'getReturnType') ? $method->getReturnType() : null;

        if (is_null($type) &&
            ($docComment = $method->getDocComment()) &&
            preg_match('#^\s*\*\s+@return\s+([^\s]+)#m', $docComment, $matches)
        ) {
            $type = $matches[1];
            if ($type == '$this') {
                $type = $method->class;
            }
        }
        if ($type == 'static') {
            $type = $method->class;
        }

        return $this->processType($type);
    }

    /**
     * Process the type.
     *
     * @param \ReflectionType|string|null $type
     * @return string
     */
    protected function processType($type)
    {
        if (! $type) {
            return '';
        }

        $type = @(string) $type;
        if ($types = explode('|', $type)) {
            $type = implode('|', array_map(function ($value) {
                if ($questionMark = substr($value, 0, 1) === '?' ? '?' : '') {
                    $value = substr($value, 1);
                }
                if (strpos($value, '\\') !== false || class_exists($value)) {
                    $value = '\\'.ltrim($value, '\\');
                }

                return $questionMark.$value;
            }, $types));
        }

        return $type ? $type.' ' : '';
    }

    /**
     * Get method's parameters.
     *
     * @param \ReflectionMethod $method
     * @return string
     */
    protected function getParameters($method)
    {
        $parameters = array_map(function ($parameter) use ($method) {
            return $this->getParameterType($method, $parameter)
                .$this->getParameterName($parameter)
                .$this->getParameterDefaultValue($method, $parameter);
        }, $method->getParameters());

        return '('.implode(', ', $parameters).')';
    }

    /**
     * Get parameter's type.
     *
     * @param \ReflectionMethod $method
     * @param \ReflectionParameter $parameter
     * @return string
     */
    protected function getParameterType($method, $parameter)
    {
        $type = method_exists($parameter, 'getType') ? $parameter->getType() : null;

        if (is_null($type) &&
            ($docComment = $method->getDocComment()) &&
            preg_match('#^\s*\*\s+@param\s+(.+?)\s+[&.]*\$'.$parameter->getName().'#m', $docComment, $matches)
        ) {
            $type = $matches[1];
            if ($type == 'mixed') {
                $type = '';
            }
        }
        if ($type == 'static') {
            $type = $method->class;
        }

        return $this->processType($type);
    }

    /**
     * Get parameter's name.
     *
     * @param \ReflectionParameter $parameter
     * @return string
     */
    protected function getParameterName($parameter)
    {
        $name = '$'.$parameter->getName();
        if (method_exists($parameter, 'isVariadic') && $parameter->isVariadic()) {
            $name = '...'.$name;
        }
        if ($parameter->isPassedByReference()) {
            $name = '&'.$name;
        }

        return $name;
    }

    /**
     * Get parameter's default value.
     *
     * @param \ReflectionMethod $method
     * @param \ReflectionParameter $parameter
     * @return string
     */
    protected function getParameterDefaultValue($method, $parameter)
    {
        if (! $parameter->isDefaultValueAvailable()) {
            return '';
        }

        $value = $parameter->getDefaultValue();
        $export = var_export($value, true);

        if ($parameter->isDefaultValueConstant()) {
            $export = $parameter->getDefaultValueConstantName();
            $export = str_replace('self::', '\\'.$method->class.'::', $export);
        } elseif (is_null($value)) {
            $export = 'null'; // NULL -> null
        } elseif (is_array($value)) {
            $export = substr($export, strlen('array ('), -2);
            $export = preg_replace(['#\n\s+#', '#\d+\s=>\s#'], [' ', ''], $export);
            $export = '['.trim($export, ', ').']';
        }

        return ' = '.$export;
    }
}

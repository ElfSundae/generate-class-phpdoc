<?php

namespace Elfsundae\Laravel;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;

class GenerateFacadePhpdocs
{
    protected array $reflections = [];

    protected int $methodModifiers = ReflectionMethod::IS_PUBLIC;
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
    public function __construct(string|object|array $classes)
    {
        if (! is_array($classes)) {
            $classes = [$classes];
        }
        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $this->reflections[$reflection->getName()] = $reflection;
        }
    }

    /**
     * Create a new generator instance.
     *
     * @param string|object|array $classes
     * @return static
     *
     * @throws \ReflectionException
     */
    public static function for(string|object|array $classes): static
    {
        return new static($classes);
    }

    /**
     * Set the method modifiers to filter the PHPDocs to include only methods
     * with certain attributes. Defaults to ReflectionMethod::IS_PUBLIC.
     *
     * @param int $modifiers Bitwise of ReflectionMethod modifiers.
     * @return $this
     */
    public function methodModifiers(int $modifiers): static
    {
        $this->methodModifiers = $modifiers;

        return $this;
    }

    /**
     * Exclude some methods by names.
     *
     * @param string|array $methodNames
     * @return $this
     */
    public function exclude(string|array $methodNames): static
    {
        $this->excluded = array_merge($this->excluded, (array) $methodNames);

        return $this;
    }

    /**
     * Set the method filter callback.
     *
     * @param (callable(ReflectionMethod): bool) $filter
     * @return $this
     */
    public function filter(callable $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Add a method doc.
     *
     * @param string $doc
     * @return $this
     */
    public function add(string $doc): static
    {
        $this->add[] = $doc;

        return $this;
    }

    /**
     * Set the "@see" classes.
     *
     * @param string|array $classes
     * @return $this
     */
    public function see(string|array $classes): static
    {
        $this->see = (array) $classes;

        return $this;
    }

    /**
     * Generates facade PHPDocs.
     *
     * @return string
     */
    public function generate(): string
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
     * Generates facade PHPDocs.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->generate();
    }

    /**
     * Return methods docs array.
     *
     * @return array
     */
    public function getMethods(): array
    {
        $docs = [];
        foreach ($this->reflections as $class => $reflection) {
            foreach ($reflection->getMethods($this->methodModifiers) as $method) {
                if ($this->excluded && in_array($method->getName(), $this->excluded)) {
                    continue;
                }
                if ($this->filter && ! call_user_func($this->filter, $method)) {
                    continue;
                }
                $docs[] = $this->getType($method->getReturnType())
                    .$method->getName()
                    .$this->getParameters($method);
            }
        }
        $docs = array_merge($docs, $this->add);
        $docs = array_values(array_unique($docs));

        return $docs;
    }

    protected function getType(?ReflectionType $type): string
    {
        if (is_null($type)) {
            return '';
        }

        $type = (string) $type;
        if ($types = explode('|', $type)) {
            $type = implode('|', array_map(function ($value) {
                if ($questionMark = substr($value, 0, 1) === '?' ? '?' : '') {
                    $value = substr($value, 1);
                }
                if (strpos($value, '\\') !== false || class_exists($value)) {
                    $value = '\\'.$value;
                }

                return $questionMark.$value;
            }, $types));
        }

        return $type ? $type.' ' : '';
    }

    protected function getParameters(ReflectionMethod $method): string
    {
        $parameters = array_map(function (ReflectionParameter $parameter) {
            return $this->getType($parameter->getType())
                .$this->getParameterName($parameter)
                .$this->getParameterDefaultValue($parameter);
        }, $method->getParameters());

        return '('.implode(', ', $parameters).')';
    }

    protected function getParameterName(ReflectionParameter $parameter): string
    {
        $name = '$'.$parameter->getName();
        if ($parameter->isVariadic()) {
            $name = '...'.$name;
        }
        if ($parameter->isPassedByReference()) {
            $name = '&'.$name;
        }

        return $name;
    }

    protected function getParameterDefaultValue(ReflectionParameter $parameter): string
    {
        if (! $parameter->isDefaultValueAvailable()) {
            return '';
        }

        $value = $parameter->getDefaultValue();
        $export = var_export($value, true);

        if ($parameter->isDefaultValueConstant()) {
            $export = $parameter->getDefaultValueConstantName();
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

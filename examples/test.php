<?php

require __DIR__.'/../vendor/autoload.php';

use Elfsundae\Laravel\GenerateFacadePhpdocs;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Foo
{
    const CONSTANT = 100;

    public function __construct(int $time = 123, $config = null)
    {
    }

    public function returnType(?Collection $date): Collection
    {
        return new Collection();
    }

    public function multiParams($a, callable $b, Collection|Arr|string|array|null $c = 'default'): array|Arr
    {
        return [];
    }

    public function array($a = [], $b = [1, 2], $c = ['foo' => 'bar']): ?string
    {
        return null;
    }

    public function constant(int $a = PHP_INT_MAX, int $b = self::CONSTANT, int $c = PATHINFO_DIRNAME | PATHINFO_BASENAME)
    {
    }

    public function reference(?int &$number = null)
    {
    }

    public function variadic(&...$numbers)
    {
    }

    public function __toString()
    {
        return static::class;
    }
}

echo GenerateFacadePhpdocs::for(Foo::class);

echo GenerateFacadePhpdocs::for(Foo::class)
    ->filter(null);

echo GenerateFacadePhpdocs::for(new Foo)
    ->exclude('__construct')
    ->see([Foo::class, GenerateFacadePhpdocs::class]);

echo GenerateFacadePhpdocs::for(GenerateFacadePhpdocs::class)
    ->filter(null)
    ->methodModifiers(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED)
    ->add('void addMethod1($param = [])')
    ->add('array addMethod2($a, $b = null)')
    ->generate();

<?php

require __DIR__.'/../vendor/autoload.php';

use Elfsundae\Laravel\GenerateFacadePhpdoc;
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

    public function void($argument)
    {
    }

    /**
     * Get return type from doc comment.
     *
     * @return string|array|null
     */
    public function docComment()
    {
    }

    public function __toString()
    {
        return static::class;
    }
}

echo GenerateFacadePhpdoc::for(Foo::class);

echo GenerateFacadePhpdoc::for(Foo::class)
    ->filter(null);

echo GenerateFacadePhpdoc::for(new Foo)
    ->exclude(['multiParams', 'reference'])
    ->see([Foo::class, GenerateFacadePhpdoc::class]);

echo GenerateFacadePhpdoc::for(GenerateFacadePhpdoc::class)
    ->filter(null)
    ->modifier(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED)
    ->add('void addMethod1($param = [])')
    ->add('array addMethod2($a, $b = null)')
    ->generate();

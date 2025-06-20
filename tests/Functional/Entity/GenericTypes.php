<?php

declare(strict_types=1);

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Functional\Entity;

/**
 * @template K of array-key
 * @template V
 */
class Collection
{
    /** @var array<K, V> */
    public array $map;

    /** @var array<V> */
    public array $list;
}

/**
 * @template T
 */
class GenericClass
{
    /** @var T */
    public mixed $genericProperty;
}

class RegularClass
{
    public string $stringProperty;
    public int $integerProperty;
}

class GenericTypes
{
    /** @var GenericClass<string> */
    public GenericClass $string;
    //    /** @var GenericClass<integer> */
    //    public GenericClass $integer;
    //    /** @var GenericClass<list<string>> */
    //    public GenericClass $stringList;
    //    /** @var GenericClass<list<integer>> */
    //    public GenericClass $integerList;
    //    /** @var GenericClass<RegularClass> */
    //    public GenericClass $regularClass;
    //
    //    /** @var Collection<string, string> */
    //    public Collection $stringStringCollection;
    //    /** @var Collection<integer, integer> */
    //    public Collection $integerIntegerCollection;
    //    /** @var Collection<integer, RegularClass> */
    //    public Collection $integerRegularClassCollection;
}

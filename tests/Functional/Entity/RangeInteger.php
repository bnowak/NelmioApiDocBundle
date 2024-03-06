<?php

declare(strict_types=1);

namespace Nelmio\ApiDocBundle\Tests\Functional\Entity;

use Symfony\Component\HttpKernel\Kernel;

trait RangeIntegerTrait
{
    /**
     * @var int<1, 99>
     */
    public $rangeInt;

    /**
     * @var int<1, max>
     */
    public $minRangeInt;

    /**
     * @var int<min, 99>
     */
    public $maxRangeInt;

    /**
     * @var int<1, 99>|null
     */
    public $nullableRangeInt;
}

if (version_compare(Kernel::VERSION, '6.1', '>=')) {
    class RangeInteger
    {
        use RangeIntegerTrait;

        /**
         * @var positive-int
         */
        public $positiveInt;

        /**
         * @var negative-int
         */
        public $negativeInt;
    }
} else {
    class RangeInteger
    {
        use RangeIntegerTrait;
    }
}

<?php

namespace Scaleplan\Notify\Structures;

use Scaleplan\Notify\Interfaces\ToArrayInterfaces;

/**
 * Class TimerLimitsStructure
 *
 * @package Scaleplan\Notify\Structures
 */
class TimerLimitsStructure implements ToArrayInterfaces
{
    public const LOW_DIVIDER = 5;
    public const LOW_MINIMUM = 20;

    public const MEDIUM_DIVIDER = 3;
    public const MEDIUM_MINIMUM = 40;

    /**
     * @var int
     */
    protected $medium;

    /**
     * @var int
     */
    protected $low;

    /**
     * TimerLimitsStructure constructor.
     *
     * @param int $startTime
     */
    public function __construct(int $startTime)
    {
        $this->low = max((int)($startTime / static::LOW_DIVIDER), self::LOW_MINIMUM);
        $this->medium = max((int)($startTime / static::MEDIUM_DIVIDER), self::MEDIUM_MINIMUM);
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return ['low' => $this->low, 'medium' => $this->medium];
    }
}

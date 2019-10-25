<?php

namespace Scaleplan\Notify\Structures;

use Scaleplan\Notify\Interfaces\ToArrayInterfaces;

/**
 * Class TimerStructure
 *
 * @package Scaleplan\Notify\Structures
 */
class TimerStructure extends AbstractStructure implements ToArrayInterfaces
{
    /**
     * @var int
     */
    protected $startTime;

    /**
     * @var TimerLimitsStructure
     */
    protected $limits;

    /**
     * @var string
     */
    protected $href;

    /**
     * @var string
     */
    protected $titleHideOn;

    /**
     * @param int $beginTime
     */
    public function calculateLimits(int $beginTime) : void
    {
        $this->limits = new TimerLimitsStructure($beginTime);
    }

    /**
     * @return int
     */
    public function getStartTime() : int
    {
        return $this->startTime;
    }

    /**
     * @param int $startTime
     */
    public function setStartTime(int $startTime) : void
    {
        $this->startTime = $startTime;
    }

    /**
     * @return TimerLimitsStructure
     */
    public function getLimits() : TimerLimitsStructure
    {
        return $this->limits;
    }

    /**
     * @param TimerLimitsStructure $limits
     */
    public function setLimits(TimerLimitsStructure $limits) : void
    {
        $this->limits = $limits;
    }

    /**
     * @return string
     */
    public function getHref() : string
    {
        return $this->href;
    }

    /**
     * @param string $href
     */
    public function setHref(string $href) : void
    {
        $this->href = $href;
    }

    /**
     * @return string
     */
    public function getTitleHideOn() : string
    {
        return $this->titleHideOn;
    }

    /**
     * @param string $titleHideOn
     */
    public function setTitleHideOn(string $titleHideOn) : void
    {
        $this->titleHideOn = $titleHideOn;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return array_merge(
            parent::toArray(),
            [
                'limits' => $this->limits->toArray(),
                'start_time' => $this->startTime,
                'href' => $this->href,
                'title_hide_on' => $this->titleHideOn,
            ]
        );
    }
}

<?php

namespace Scaleplan\Notify\Structures;

/**
 * Class TimerStructure
 *
 * @package Scaleplan\Notify\Structures
 */
class TimerStructure extends AbstractStructure
{
    /**
     * @var int
     */
    protected $startTime;

    /**
     * @var int
     */
    protected $initTime;

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
     * TimerStructure constructor.
     */
    public function __construct()
    {
        $this->initTime = time();
    }

    public function calculateLimits() : void
    {
        $this->limits = new TimerLimitsStructure($this->startTime);
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
     * @return int
     */
    public function getInitTime() : int
    {
        return $this->initTime;
    }

    /**
     * @param int $initTime
     */
    public function setInitTime(int $initTime) : void
    {
        $this->initTime = $initTime;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return array_merge(
            parent::toArray(),
            [
                'limits'        => $this->limits->toArray(),
                'start_time'    => $this->startTime,
                'href'          => $this->href,
                'title_hide_on' => $this->titleHideOn,
                'init_time'     => $this->initTime,
            ]
        );
    }
}

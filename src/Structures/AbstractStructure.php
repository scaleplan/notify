<?php

namespace Scaleplan\Notify\Structures;

use Scaleplan\Notify\Interfaces\ToArrayInterfaces;

/**
 * Class AbstractStructure
 *
 * @package Scaleplan\Notify\Structures
 */
abstract class AbstractStructure implements ToArrayInterfaces
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @return string
     */
    public function getMessage() : string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message) : void
    {
        $this->message = $message;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return ['message' => $this->message];
    }
}

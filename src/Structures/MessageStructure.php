<?php

namespace Scaleplan\Notify\Structures;

use Scaleplan\Notify\Constants\Statuses;
use Scaleplan\Notify\Exceptions\StructureException;

/**
 * Class MessageStructure
 *
 * @package Scaleplan\Notify\Structures
 */
class MessageStructure extends AbstractStructure
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @throws StructureException
     */
    public function setStatus(string $status) : void
    {
        if (!\in_array($status, Statuses::ALL, true)) {
            throw new StructureException("Статус $status не существует.");
        }

        $this->status = $status;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return array_merge(parent::toArray(), ['status' => $this->status]);
    }
}

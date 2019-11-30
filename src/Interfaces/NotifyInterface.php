<?php

namespace Scaleplan\Notify\Interfaces;

use Scaleplan\Notify\Structures\AbstractStructure;

/**
 * Interface NotifyInterface
 *
 * @package Scaleplan\Notify\Interfaces
 */
interface NotifyInterface
{
    /**
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     */
    public function send(string $channelName, string $eventName, AbstractStructure $data) : void;

    /**
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     */
    public function guaranteedSend(string $channelName, string $eventName, AbstractStructure $data) : void;

    /**
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     */
    public function pinNotify(string $channelName, string $eventName, AbstractStructure $data) : void;

    /**
     * @param array $channelNames
     */
    public function sendOld(array $channelNames) : void;

    /**
     * @param string $channelName
     * @param string $socketId
     * @param int $userId
     *
     * @return string
     */
    public function getAuthKey(string $channelName, string $socketId, int $userId) : string;
}

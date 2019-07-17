<?php

namespace Scaleplan\Notify\Interfaces;

use Scaleplan\Notify\Structures\AbstractStructure;

/**
 * Class Notify
 *
 * @package Scaleplan\Notify
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
     * @param int[] $users
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     */
    public function guaranteedSend(array $users, string $channelName, string $eventName, AbstractStructure $data) : void;

    /**
     * @param int[] $users
     */
    public function sendOld(array $users) : void;

    /**
     * @param string $channelName
     * @param string $socketId
     * @param int $userId
     *
     * @return string
     */
    public function getAuthKey(string $channelName, string $socketId, int $userId) : string;
}

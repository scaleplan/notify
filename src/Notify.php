<?php

namespace Scaleplan\Notify;

use Pusher\Pusher;
use Scaleplan\Notify\Exceptions\NotifyException;
use Scaleplan\Notify\Interfaces\NotifyInterface;
use Scaleplan\Notify\Structures\AbstractStructure;
use Scaleplan\Notify\Structures\StructureFabric;
use Scaleplan\Notify\Structures\TimerStructure;
use Scaleplan\Redis\RedisSingleton;
use function Scaleplan\Helpers\get_required_env;

/**
 * Class Notify
 *
 * @package Scaleplan\Notify
 */
class Notify implements NotifyInterface
{
    public const REDIS_NOTIFICATIONS_KEY_POSTFIX = 'notifications';
    public const PINNED_FLAG                     = 'pinned';

    /**
     * @var Pusher
     */
    private $pusher;

    /**
     * @var string
     */
    private $key;

    /**
     * AbstractNotify constructor.
     *
     * @param string $namespace
     *
     * @throws \Pusher\PusherException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     */
    public function __construct(string $namespace)
    {
        $this->key = $namespace . ':' . static::REDIS_NOTIFICATIONS_KEY_POSTFIX;
        $this->pusher = new Pusher(
            get_required_env('PUSHER_APP_KEY'),
            get_required_env('PUSHER_APP_SECRET'),
            get_required_env('PUSHER_APP_ID'),
            ['cluster' => get_required_env('PUSHER_APP_CLUSTER'), 'useTLS' => true]
        );
    }

    /**
     * @return \Redis
     *
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    protected function getRedis() : \Redis
    {
        static $redis;
        if (!$redis) {
            $redis = RedisSingleton::create(
                get_required_env('REDIS_SOCKET'),
                get_required_env('REDIS_PORT'),
                get_required_env('REDIS_TIMEOUT')
            );
        }

        return $redis;
    }

    /**
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     *
     * @throws NotifyException
     * @throws \Pusher\PusherException
     */
    public function send(string $channelName, string $eventName, AbstractStructure $data) : void
    {
        $onlineUsers = $this->getOnlineUsers($channelName);
        if ($onlineUsers) {
            $this->pusher->trigger($channelName, $eventName, $data->toArray());
        }
    }

    /**
     * @param string $channelName
     *
     * @return array
     *
     * @throws NotifyException
     * @throws \Pusher\PusherException
     */
    protected function getOnlineUsers(string $channelName) : array
    {
        $response = $this->pusher->get("/channels/presence-$channelName/users");
        if (!$response
            || $response['status'] !== 200
            || !\array_key_exists('users', $onlineUsersArray = json_decode($response['body'], true))
        ) {
            throw new NotifyException('Pusher.com not available.');
        }

        return array_column($onlineUsersArray['users'], 'id');
    }

    /**
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     *
     * @throws NotifyException
     * @throws \Pusher\PusherException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    public function guaranteedSend(string $channelName, string $eventName, AbstractStructure $data) : void
    {
        $onlineUsers = $this->getOnlineUsers($channelName);
        if (!$onlineUsers) {
            $this->saveToRedis($channelName, $eventName, $data);
            return;
        }

        if ($onlineUsers) {
            $this->pusher->trigger($channelName, $eventName, $onlineUsers);
        }
    }

    /**
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     *
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    public function pinNotify(string $channelName, string $eventName, AbstractStructure $data) : void
    {
        if ($data instanceof TimerStructure) {
            $currentStartTime = $data->getStartTime() - (time() - $data->getInitTime());
            if ($currentStartTime <= 0) {
                return;
            }

            $data->setInitTime(time());
            $data->setStartTime($currentStartTime);
        }

        $this->saveToRedis($channelName, $eventName, $data, true);
    }

    /**
     * @param string $channelName
     *
     * @return string
     */
    protected function getChannelRedisName(string $channelName) : string
    {
        return "{$this->key}:$channelName";
    }

    /**
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     * @param bool $isPinned
     *
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    protected function saveToRedis(
        string $channelName,
        string $eventName,
        AbstractStructure $data,
        bool $isPinned = false
    ) : void
    {
        $hashKey = "$eventName:" . ($isPinned ? static::PINNED_FLAG : '');
        $key = $this->getChannelRedisName($channelName);
        $currentRecord = json_decode($this->getRedis()->hGet($key, $hashKey), true) ?? [];
        $currentRecord[] = $data->toArray();
        $this->getRedis()->hSet($key, $hashKey, json_encode($currentRecord, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array $channelNames
     *
     * @throws NotifyException
     * @throws \Pusher\PusherException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    public function sendOld(array $channelNames) : void
    {
        foreach ($channelNames as $channelName) {
            $key = $this->getChannelRedisName($channelName);
            foreach ($this->getRedis()->hGetAll($key) as $hashKey => $datas) {
                [$eventName, $isPinned] = explode(':', $hashKey);
                $isPinned = $isPinned === static::PINNED_FLAG;
                $this->getRedis()->del($key);
                foreach (json_decode($datas, true) as $data) {
                    $data = StructureFabric::getStructure($data);
                    if ($isPinned) {
                        $this->pinNotify($channelName, $eventName, $data);
                        $this->send($channelName, $eventName, $data);
                        continue;
                    }

                    $this->guaranteedSend($channelName, $eventName, $data);
                }
            }
        }
    }

    /**
     * @param string $channelName
     * @param string $eventName
     * @param string $message
     *
     * @throws Exceptions\StructureException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    public function unpinNotify(string $channelName, string $eventName, string $message) : void
    {
        $key = $this->getChannelRedisName($channelName);
        $hashKey = $eventName . ':' . static::PINNED_FLAG;
        $datas = json_decode($this->getRedis()->hGet($key, $hashKey), true);
        foreach ($datas as $index => $data) {
            $data = StructureFabric::getStructure($data);
            if ($data->getMessage() === $message) {
                unset($datas[$index]);
            }
        }

        $this->getRedis()->hSet($key, $hashKey, json_encode($datas, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param string $channelName
     * @param string $socketId
     * @param int $userId
     *
     * @return string
     *
     * @throws \Pusher\PusherException
     */
    public function getAuthKey(string $channelName, string $socketId, int $userId) : string
    {
        return $this->pusher->presence_auth($channelName, $socketId, $userId);
    }
}

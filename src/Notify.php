<?php

namespace Scaleplan\Notify;

use Pusher\Pusher;
use Scaleplan\Notify\Exceptions\NotifyException;
use Scaleplan\Notify\Interfaces\NotifyInterface;
use Scaleplan\Notify\Structures\AbstractStructure;
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
     * @throws \Pusher\PusherException
     */
    public function send(string $channelName, string $eventName, AbstractStructure $data) : void
    {
        $this->pusher->trigger($channelName, $eventName, $data->toArray());
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
        $response = $this->pusher->presence_auth("presence-$channelName");
        if (!$response
            || $response['status'] !== 200
            || !\array_key_exists('users', $onlineUsersArray = json_decode($response['body'], true))
        ) {
            throw new NotifyException('Pusher.com not available.');
        }

        return array_column($onlineUsersArray['users'], 'id');
    }

    /**
     * @param array $users
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     *
     * @throws NotifyException
     * @throws \Pusher\PusherException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    public function guaranteedSend(array $users, string $channelName, string $eventName, AbstractStructure $data) : void
    {
        $onlineUsers = $this->getOnlineUsers($channelName);
        if ($disconnectUsers = array_diff($users, $onlineUsers)) {
            $this->saveToRedis($disconnectUsers, $channelName, $eventName, $data);
        }

        $this->pusher->trigger($channelName, $eventName, $onlineUsers);
    }

    /**
     * @param array $users
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     *
     * @throws NotifyException
     * @throws \Pusher\PusherException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    public function pinNotify(array $users, string $channelName, string $eventName, AbstractStructure $data) : void
    {
        $this->saveToRedis($users, $channelName, $eventName, $data, true);

        $onlineUsers = $this->getOnlineUsers($channelName);
        $this->pusher->trigger($channelName, $eventName, $onlineUsers);
    }

    /**
     * @param array $users
     * @param string $channelName
     * @param string $eventName
     * @param AbstractStructure $data
     * @param bool $isPinned
     *
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    protected function saveToRedis(
        array $users,
        string $channelName,
        string $eventName,
        AbstractStructure $data,
        bool $isPinned = false
    ) : void
    {
        foreach ($users as $user) {
            $key = "{$this->key}:$user";
            $hashKey = "$channelName:$eventName:" . ($isPinned ? static::PINNED_FLAG : '');
            $currentRecord = json_decode($this->getRedis()->hGet($key, $hashKey), true) ?? [];
            $currentRecord[] = $data->toArray();
            $this->getRedis()->hSet(
                "{$this->key}:$user",
                "$channelName:$eventName",
                json_encode($currentRecord, JSON_UNESCAPED_UNICODE)
            );
        }
    }

    /**
     * @param array $users
     *
     * @throws NotifyException
     * @throws \Pusher\PusherException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    public function sendOld(array $users) : void
    {
        foreach ($users as $user) {
            $key = "{$this->key}:$user";
            foreach ($this->getRedis()->hGetAll($key) as $channel => $data) {
                [$channelName, $eventName, $isPinned] = explode(':', $channel);

                $this->getRedis()->hDel($key, $channel);

                $isPinned = $isPinned === static::PINNED_FLAG;
                if ($isPinned) {
                    $this->pinNotify([$user], $channelName, $eventName, $data);
                    continue;
                }

                $this->guaranteedSend([$user], $channelName, $eventName, $data);
            }
        }
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

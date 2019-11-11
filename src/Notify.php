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
        $response = $this->pusher->get("/channels/$channelName/users");
        if (!$response
            || $response['status'] !== 200
            || !\array_key_exists('users', $onlineUsersArray = json_decode($response['body'], true))
        ) {
            throw new NotifyException('Pusher.com not available.');
        }

        $onlineUsers = array_column($onlineUsersArray['users'], 'id');
        if ($disconnectUsers = array_diff($users, $onlineUsers)) {
            foreach ($disconnectUsers as $user) {
                $this->getRedis()->hSet(
                    "{$this->key}:$user",
                    "$channelName:$eventName",
                    json_encode($data->toArray(), JSON_UNESCAPED_UNICODE)
                );
            }
        }

        $this->pusher->trigger($channelName, $eventName, $onlineUsers);
    }

    /**
     * @param int[] $users
     *
     * @throws \Pusher\PusherException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Redis\Exceptions\RedisSingletonException
     */
    public function sendOld(array $users) : void
    {
        foreach ($users as $user) {
            foreach ($this->getRedis()->hGetAll("{$this->key}:$user") as $channel => $data) {
                [$channelName, $eventName] = explode(':', $channel);
                $this->pusher->trigger($channelName, $eventName, $data, false, true);
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

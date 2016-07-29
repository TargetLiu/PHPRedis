<?php

namespace TargetLiu\PHPRedis\Queue;

use Illuminate\Support\Arr;
use Illuminate\Queue\Connectors\ConnectorInterface;

class PHPRedisConnector implements ConnectorInterface
{
    /**
     * The Redis database instance.
     *
     * @var \Redis
     */
    protected $redis;


    /**
     * Create a new Redis queue connector instance.
     *
     * @param  \Redis  $redis
     * @return void
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $queue = new PHPRedisQueue(
            $this->redis, $config['queue']
        );

        $queue->setExpire(Arr::get($config, 'expire', 60));

        return $queue;
    }
}

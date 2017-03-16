<?php

namespace TargetLiu\PHPRedis\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use TargetLiu\PHPRedis\Database;

class PHPRedisQueue extends Queue implements QueueContract
{
    /**
     * The Redis database instance.
     *
     * @var \Redis
     */
    protected $redis;

    /**
     * The connection name.
     *
     * @var string
     */
    protected $connection;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $default;

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected $expire = 60;

    /**
     * Create a new Redis queue instance.
     *
     * @param  \Redis  $redis
     * @param  string  $default
     * @param  string  $connection
     * @return void
     */
    public function __construct(Database $redis, $default = 'default', $connection = 'default')
    {
        $this->redis = $redis;
        $this->default = $default;
        $this->connection = $connection;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        $queue = $this->getQueue($queue);

        return $this->getConnection()->eval(LuaScripts::size(), 3, $queue, $queue.':delayed', $queue.':reserved');
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $this->getConnection()->rPush($this->getQueue($queue), $payload);

        return Arr::get(json_decode($payload, true), 'id');
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $payload = $this->createPayload($job, $data);

        $delay = $this->getSeconds($delay);

        $this->getConnection()->zAdd($this->getQueue($queue) . ':delayed', $this->getTime() + $delay, $payload);

        return Arr::get(json_decode($payload, true), 'id');
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param  string  $queue
     * @param  string  $payload
     * @param  int  $delay
     * @param  int  $attempts
     * @return void
     */
    public function release($queue, $payload, $delay, $attempts)
    {
        $payload = $this->setMeta($payload, 'attempts', $attempts);

        $this->getConnection()->zAdd($this->getQueue($queue) . ':delayed', $this->getTime() + $delay, $payload);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $original = $queue ?: $this->default;

        $queue = $this->getQueue($queue);

        if (!is_null($this->expire)) {
            $this->migrateAllExpiredJobs($queue);
        }
        $job = $this->getConnection()->lPop($queue);

        if ($job) {
            $this->getConnection()->zAdd($queue . ':reserved', $this->getTime() + $this->expire, $job);

            return new PHPRedisJob($this->container, $this, $job, $original);
        }
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string  $queue
     * @param  string  $job
     * @return void
     */
    public function deleteReserved($queue, $job)
    {
        $this->getConnection()->zRem($this->getQueue($queue) . ':reserved', $job);
    }

    /**
     * Migrate all of the waiting jobs in the queue.
     *
     * @param  string  $queue
     * @return void
     */
    protected function migrateAllExpiredJobs($queue)
    {
        $this->migrateExpiredJobs($queue . ':delayed', $queue);

        $this->migrateExpiredJobs($queue . ':reserved', $queue);
    }

    /**
     * Migrate the delayed jobs that are ready to the regular queue.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function migrateExpiredJobs($from, $to)
    {
        //$options = ['cas' => true, 'watch' => $from, 'retry' => 10];
        $attempts = 10;

        do {
            //echo $attempts.'';

            $jobs = $this->getExpiredJobs(
                $this->getConnection(), $from, $time = $this->getTime()
            );

            //var_dump($jobs);

            if (count($jobs) > 0) {
                $this->removeExpiredJobs($this->getConnection(), $from, $time);

                $this->pushExpiredJobsOntoNewQueue($this->getConnection(), $to, $jobs);
            }
            //var_dump($queue->isEmpty());

            /*if ($queue->isEmpty()) {
                $this->getConnection()->unwatch($from);
                $this->getConnection()->discard();
                return;
            }*/

            $execResponse = $this->getConnection()->exec();
            //var_dump( $execResponse);

            if ($execResponse === null) {
                continue;
            }

            break;

        } while ($attempts-- > 0);

    }

    /**
     * Get the expired jobs from a given queue.
     *
     * @param  \Redis  $transaction
     * @param  string  $from
     * @param  int  $time
     * @return array
     */
    protected function getExpiredJobs($transaction, $from, $time)
    {
        return $transaction->zRangeByScore($from, '-inf', $time);
    }

    /**
     * Remove the expired jobs from a given queue.
     *
     * @param  \Redis  $transaction
     * @param  string  $from
     * @param  int  $time
     * @return void
     */
    protected function removeExpiredJobs($transaction, $from, $time)
    {
        $transaction->watch($from);

        $transaction->multi();

        $transaction->zRemRangeByScore($from, '-inf', $time);
    }

    /**
     * Push all of the given jobs onto another queue.
     *
     * @param  \Redis  $transaction
     * @param  string  $to
     * @param  array  $jobs
     * @return void
     */
    protected function pushExpiredJobsOntoNewQueue($transaction, $to, $jobs)
    {
        call_user_func_array([$transaction, 'rPush'], array_merge([$to], $jobs));
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        $payload = parent::createPayload($job, $data);

        $payload = $this->setMeta($payload, 'id', $this->getRandomId());

        return $this->setMeta($payload, 'attempts', 1);
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId()
    {
        return Str::random(32);
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return 'queues:' . ($queue ?: $this->default);
    }

    /**
     * Get the connection for the queue.
     *
     * @return \Redis
     */
    protected function getConnection()
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Get the underlying Redis instance.
     *
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Get the expiration time in seconds.
     *
     * @return int|null
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * Set the expiration time in seconds.
     *
     * @param  int|null  $seconds
     * @return void
     */
    public function setExpire($seconds)
    {
        $this->expire = $seconds;
    }
}

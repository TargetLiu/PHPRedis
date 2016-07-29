<?php

namespace TargetLiu\PHPRedis\Queue;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;
        Queue::addConnector('phpredis', function () use ($app) {
            return new PHPRedisConnector($app['phpredis']);
        });
    }
}

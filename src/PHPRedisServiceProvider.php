<?php

namespace TargetLiu\PHPRedis;

use Illuminate\Support\ServiceProvider;

class PHPRedisServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('phpredis', function ($app) {
            $app->configure('database');
            $redis = new \Redis;
            $redis->pconnect($app->config['database.redis.default.host']);

            if (!empty($app->config['database.redis.default.password'])) {
                $redis->auth($app->config['database.redis.default.password']);
            }
            
            if (!empty($app->config['database.redis.default.database'])) {
                $redis->select($app->config['database.redis.default.database']);
            }
            return $redis;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['phpredis'];
    }
}

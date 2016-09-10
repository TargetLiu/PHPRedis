<?php

namespace TargetLiu\PHPRedis\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Cache::extend('phpredis', function ($app) {
            return Cache::repository(new PHPRedisStore($app->make('phpredis'), $app->config['cache.prefix'], $app->config['cache.stores.phpredis.connection']));
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

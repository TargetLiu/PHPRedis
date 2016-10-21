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
        class_alias('TargetLiu\PHPRedis\PHPRedis', 'PHPRedis');
        $this->app->singleton('phpredis', function ($app) {
            $app->configure('database');
            return new Database($app->config['database.redis']);
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

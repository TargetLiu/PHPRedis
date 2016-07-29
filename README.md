# [PhpRedis](https://github.com/phpredis/phpredis) for Lumen 5.*

## Update
### 1.1.0

1. Move cache driver to `Target\PHPRedis\Cache` .
2. Add a Queue driver.

## Installation

- Install [PhpRedis](https://pecl.php.net/package/redis)
- Run `composer require targetliu/phpredis`
- Configure redis in *.env*

### Basic

- Add `$app->register(TargetLiu\PHPRedis\PHPRedisServiceProvider::class);` in *bootstrap/app.php*

### Cache Driver

- Add `$app->register(TargetLiu\PHPRedis\Cache\CacheServiceProvider::class);` in *bootstrap/app.php* in order to use PhpRedis with Lumen cache
- Add 

```
'phpredis' => [
    'driver' => 'phpredis'
],
```

to **stores** in *config/cache.php* or *vendor/larvel/lumen-framework/config/app.php* in order to use PhpRedis with Lumen cache

- Set `CACHE_DRIVER=phpredis` in *.env*


### Queue Driver

- Add `$app->register(TargetLiu\PHPRedis\Queue\QueueServiceProvider::class);` in *bootstrap/app.php* in order to use PhpRedis with Lumen queue
- Add 

```
'phpredis' => [
    'driver'     => 'phpredis',
	'connection' => 'default',
	'queue'      => 'default',
	'expire'     => 60,
],
```

to **connections** in *config/queue.php* or *vendor/larvel/lumen-framework/config/queue.php* in order to use PhpRedis with Lumen queue

- Set `QUEUE_DRIVER=phpredis` in *.env*

## Usage

- With `app('phpredis')` , read [PhpRedis document](https://github.com/phpredis/phpredis) 
- With `app('cache')` , read [Lumen document](https://lumen.laravel.com/docs/5.2/cache) 
- With `app('queue')` , read [Lumen document](https://lumen.laravel.com/docs/5.2/queues) 

## Important

**This is just an example.**
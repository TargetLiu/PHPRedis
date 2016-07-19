# [PhpRedis](https://github.com/phpredis/phpredis) for Lumen 5.*

## Installation

- Install [PhpRedis](https://pecl.php.net/package/redis)
- Run `composer require targetliu/phpredis`
- Add `$app->register(TargetLiu\PHPRedis\PHPRedisServiceProvider::class);` in *bootstrap/app.php*
- Add `$app->register(TargetLiu\PHPRedis\CacheServiceProvider::class);` in *bootstrap/app.php* in order to use PhpRedis with Lumen cache
- Add 

```
'phpredis' => [
    'driver' => 'phpredis'
],
```

to **stores** in *config/cache.php* or *vendor/larvel/lumen-framework/config/app.php* in order to use PhpRedis with Lumen cache

- Configure redis in *.env*
- Set `CACHE_DRIVER=phpredis` in *.env*

## Usage

- With `app('phpredis')` , read [PhpRedis document](https://github.com/phpredis/phpredis) 
- With `app('cache')` , read [Lumen document](https://lumen.laravel.com/docs/5.2/cache) 

## Important

**This is just an example.**
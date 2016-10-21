<?php
namespace TargetLiu\PHPRedis;

use Illuminate\Support\Facades\Facade;

/**
 *
 *
 * User: jileilei
 * Date: 2016/10/21
 * Time: 下午5:24
 */
class PHPRedis extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'phpredis';
    }
}
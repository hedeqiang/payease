<?php

namespace Hedeqiang\Yizhifu\Facades;

use Illuminate\Support\Facades\Facade;

class Pay extends Facade
{
    /**
     * Return the facade accessor.
     *
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'yizhifu';
    }

    /**
     * Return the facade accessor.
     *
     * @return \Hedeqiang\Yizhifu\Pay
     */
    public static function pay()
    {
        return app('yizhifu');
    }

}
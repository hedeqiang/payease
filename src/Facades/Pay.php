<?php

/*
 * This file is part of the hedeqiang/payease.
 *
 * (c) hedeqiang <laravel_code@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Hedeqiang\PayEase\Facades;

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
        return 'pay';
    }

    /**
     * Return the facade accessor.
     *
     * @return \Hedeqiang\PayEase\Pay
     */
    public static function pay()
    {
        return app('pay');
    }
}

<?php

/*
 * This file is part of the hedeqiang/payease.
 *
 * (c) hedeqiang <laravel_code@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Hedeqiang\PayEase;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/payease.php' => config_path('payease.php'),
        ], 'payease');
    }

    public function register()
    {
        $this->app->singleton(Pay::class, function () {
            return new Pay(config('payease'));
        });

        $this->app->alias(Pay::class, 'pay');
    }

    public function provides(): array
    {
        return ['pay'];
    }
}

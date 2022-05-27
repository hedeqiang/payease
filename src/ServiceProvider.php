<?php

namespace Hedeqiang\Yizhifu;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/yizhifu.php' => config_path('yizhifu.php'),
        ], 'yizhifu');
    }

    public function register()
    {
        $this->app->singleton(Pay::class, function () {
            return new Pay(config('yizhifu'));
        });

        $this->app->alias(Pay::class, 'yizhifu');

    }

    public function provides(): array
    {
        return ['yizhifu'];
    }
}

<?php

/*
 * This file is part of the hedeqiang/yizhifu.
 *
 * (c) hedeqiang <laravel_code@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Hedeqiang\Yizhifu;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                Pay::class => static function (ContainerInterface $container) {
                    return new Pay($container->get(ConfigInterface::class)->get('yizhifu', []));
                },
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for yizhifu.',
                    'source' => __DIR__.'/Config/yizhifu.php',
                    'destination' => BASE_PATH.'/config/autoload/yizhifu.php',
                ],
            ],
        ];
    }
}

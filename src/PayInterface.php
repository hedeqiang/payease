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

interface PayInterface
{
    public function request($path, $params): array;

    public function handleNotify(): array;
}

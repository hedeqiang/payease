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

use yii\base\Component;

class YiiPay extends Component
{
    /**
     * @var array
     */
    public $options = [];

    /**
     * @var Pay
     */
    private static $_pay;

    /**
     * @return Pay
     */
    public function getPay(array $options = [])
    {
        $options and $this->options = array_merge($this->options, $options);
        if (!static::$_pay instanceof Pay || $options) {
            static::$_pay = new Pay($this->options);
        }

        return static::$_pay;
    }
}

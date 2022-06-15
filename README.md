# 首信易支付 SDK for PHP

[![PHP Version Require](http://poser.pugx.org/hedeqiang/payease/require/php)](https://packagist.org/packages/hedeqiang/payease)
[![Latest Stable Version](https://poser.pugx.org/hedeqiang/payease/v)](//packagist.org/packages/hedeqiang/payease)
[![Total Downloads](https://poser.pugx.org/hedeqiang/payease/downloads)](//packagist.org/packages/hedeqiang/payease)
[![Latest Unstable Version](https://poser.pugx.org/hedeqiang/payease/v/unstable)](//packagist.org/packages/hedeqiang/payease)
[![License](https://poser.pugx.org/hedeqiang/payease/license)](//packagist.org/packages/hedeqiang/payease)
[![Tests](https://github.com/hedeqiang/payease/actions/workflows/test.yml/badge.svg)](https://github.com/hedeqiang/payease/actions/workflows/test.yml)


参考文档 [首信易支付](https://demo.yizhifubj.com/Development/showdoc-master/web/#/5?page_id=242) 、[首信易支付](https://www.payeasenet.com/)

## 环境需求

- PHP >= 7.1
- [Composer](https://getcomposer.org/) >= 2.0

支持主流框架 `Laravel`、`Hyperf`、`Yii2` 快捷使用，具体使用方法请滑到底部

## Installing

```shell
$ composer require hedeqiang/payease -vvv
```

## Usage
```shell
require __DIR__ .'/vendor/autoload.php';
use Hedeqiang\PayEase\Pay;
$app = new Pay([
    'privateKey' => '/parth/client.pfx',
    'publicKey'  => 'path/test.cer',
    'merchantId' => '890000593',
    'password'   => '123456',
]);
```

> 本 SDK 已经处理好 hmac 签名，用户不需要传递此参数，`merchantId` 可传可不传

### 立即下单
```shell
$uri = 'onlinePay/order';

$params = [
    "callbackUrl"    => "https://demo.5upay.com/sdk/callback",
    "clientIp"       => "10.101.10.10",
    "hmac"           => null,
    "notifyUrl"      => "https://demo.5upay.com/sdk/onlinepay/notify",
    "orderAmount"    => "1",
    "orderCurrency"  => "CNY",
    "payer"          => new \stdClass(),
//    "payer" => [
//        "bankCardNum" =>"6217000xxxxx",
//        "email" =>"laravel_code@163.com",
//        "idNum" =>"xxxx",
//        "idType" =>"IDCARD",
//        "name" =>"xxx",
//        "phoneNum" =>"xxx"
//    ],
    // "paymentModeCode" =>"BANK_CARD-B2C-ICBC-P2P",
    "productDetails" => [
        [
            "amount"      => "1",
            "description" => "黑色64G",
            "name"        => "IPHONE6",
            "quantity"    => "100",
            "receiver"    => "张三"
        ]
    ],
    "remark"         => "备注",
    "requestId"      => time(),
    "timeout"        => "10"
];
$app->request($uri,$params)
```
#### 立即下单返回示例
> 客户端 使用 redirectUrl 发起 get 请求即可发起收银台支付
```shell
{
    "redirectUrl": "https://payment.5upay.com/receipt/index/db50e5bac76e40b18cbd5da1ce238cca",
    "merchantId": "890000593",
    "requestId": "1653644302",
    "hmac": "F9apHKePPFzC3Xp9Dxafd8m0/NhDLuxlwCqxTZtDBqtFyoA31pRlsulOxYDGVXI0o73XUUtzfLpu+ghGo1CQM+r6wqp/vE0UCv7CYWlay5de0A7MmtKpavgDengt7mvht9RL5cmvZS7RkYEsEde84n21LNxscjlRM2kl8AGUupqzDh0nbwgkzfOBeGKPjTvklqFgrjdPkgBhxDP9QZxcQvnD4c4vML27sjqA2FaUxxl2qj9SGPFkzGQ/slX9zMfWbDRWnmvtDF9j0/Uq/LshaBlAa34zUcWumed357Tcmwqe6poIQtThn5clBtBbH9c5ZQBZYkCis5nes+MZVKr5Gw==",
    "paymentOrderId": "db50e5bac76e40b18cbd5da1ce238cca",
    "status": "REDIRECT"
}
```

### 回调通知
```shell
$result = $app->handleNotify();
// TODO

return 'SUCCESS' ; // retuen 'Fail';
```

#### 回调返回示例
> 正常情况下 返回 array，支付成功 
```shell
 array (
  'cardType' => 'CREDIT_CARD',
  'clearingOrg' => 'UNION_PAY',
  'completeDateTime' => '2022-05-27 16:31:31',
  'merchantId' => '890000593',
  'orderAmount' => '1',
  'orderCurrency' => 'CNY',
  'paymentModeAlias' => 'WAP',
  'realBankRequestNumber' => '4itnydmtesuN',
  'realBankSerialNumber' => '862205271630378302598',
  'remark' => '备注',
  'requestId' => '1653640218',
  'serialNumber' => 'c7117326f3ab4d4c94bf5755c38e1793',
  'status' => 'SUCCESS',
  'totalRefundAmount' => '0',
  'totalRefundCount' => '0',
) 
```
> ps: 注意未支付、取消支付也是有回调的，也会发到你的回调 URL 地址上
#### 未支付、取消支付回调示例
```shell
array (
  'completeDateTime' => '2022-05-27 16:38:34',
  'merchantId' => '890000593',
  'orderAmount' => '1',
  'orderCurrency' => 'CNY',
  'remark' => '备注',
  'requestId' => '1653640093',
  'serialNumber' => '32d0e64a43df4561973e47733ffd9f02',
  'status' => 'CANCEL',
  'totalRefundAmount' => '0',
  'totalRefundCount' => '0',
) 
```

### 查询订单
```shell
$uri = "onlinePay/query";
$params = [
    'merchantId' => '890000593',
    'requestId' => '1653659465'
];

$sreult = $app->request($uri,$params);
```

### 二级商户入网
```php
$uri = 'serviceprovider/declaration/declare';

$params = [
    'requestId'           => time(),
    'operationType'       => 'CREATE',
    'notifyUrl'           => 'https://www.5upay.com/callback.action?test=test',
    'extendedParameters'  => 'autoReview:FALSE,sendActiveEmail:TRUE',
    'baseInfo'            => [
        'signedType'              => 'BY_SPLIT_BILL',
        'signedName'              => 'xxx',  //签约名
        'registerRole'            => 'NATURAL_PERSON',
        'signedShorthand'         => 'xxx',
        'businessAddressProvince' => '110000', //经营地址省
        'businessAddressCity'     => '110100', //经营地址市
        'businessAddressArea'     => '110106', // 经营地址区
        'businessAddress'         => '北京市', //经营地址
        'contactName'             => 'xxx', // 联系人姓名
        'contactEmail'            => 'xxx@163.com', //联系人邮箱
        'contactPhone'            => 'xxx',// 联系人电话
        //'businessClassification'  => 'INTERNAL_TESTING_01', //业务分类
        'desireAuth'              => 'DESIRE_MOBILEINFO', //开户意愿核实类型

    ],
    'bankCardInfo'        => [
        'accountName'      => 'xxx', // 开户名称
        'bankCardNo'       => 'xxx',
        'provinceCode'     => '130000',
        'cityCode'         => '130100',
        'liquidationType'  => 'SETTLE', //清算方式
        'accountType'      => 'PRIVATE', //结算银行卡属性
        'withdrawRateType' => 'SINGLE',
    ],
    'desireAuthInfo'      => [
        'legalPersonName'    => 'xxx', //法人姓名
        'legalPersonIdNo'    => 'xxx', // 法人身份证号
        'legalPersonPhoneNo' => 'xxx', // 法人手机号
    ],
    'certificateInfo'     => [
        'legalPersonName'         => 'xxx',
        'profession'              => '1',// 法人职业
        'legalPersonIdType'       => 'IDCARD',
        'legalPersonIdNo'         => 'xxx',
        'legalIdCardProsPath'     => '/serviceprovider/TestData/111.jpg', //法人证件人像面路径
        'legalIdCardConsPath'     => '/serviceprovider/TestData/111.jpg', //法人证件国徽面路径
        'holdingIdCardPath'       => '/serviceprovider/TestData/111.jpg', //法人手持证件照
        'legalPersonBankCardPath' => '/serviceprovider/TestData/111.jpg', //法人银行卡图影印件路径
        'legalPersonPhone'        => 'xxx'
    ],
    'certificateContacts' => [
        [
            'name'   => 'xxx',
            'idType' => 'IDCARD',
            'idNo'   => 'xxx',
        ]
    ],
    'contractInfo'        => [
//                'receiverAddress' => 'c',
//                'receiverName'    => 'xxx',
//                'receiverPhone'   => 'xxx',
        'contractType'    => 'ELECTRON',
    ],
//            'paymentProfiles'    => [
//                [
//                    'paymentMode' => 'B2C',
//                    'feeType'     => 'SINGLE',
//                ]
//
//            ],
//

];

return $app->request($uri, $params);
```


## 在 Laravel 中使用
#### 发布配置文件
```php
php artisan vendor:publish --tag=payease
or 
php artisan vendor:publish --provider="Hedeqiang\PayEase\ServiceProvider"
```

##### 编写 .env 文件
```
PAYEASE_PRIVATIVE_KEY=
PAYEASE_PUBLIC_KEY=
PAYEASE_MERCHAN_ID=
PAYEASE_PASSWORD=
```

### 使用

#### 服务名访问

```php
public function index()
{
    return app('pay')->request($uri,$params);
}
```

#### Facades 门面使用(可以提示)

```php
use Hedeqiang\PayEase\Facades\Pay;

public function index()
{
   return Pay::pay()->request($uri,$params)
}

public function notify(Request $request)
{
   $result = Pay::pay()->handleNotify();
}
```

## 在 Hyperf 中使用
#### 发布配置文件
```php
php bin/hyperf.php vendor:publish hedeqiang/payease
```

##### 编写 .env 文件
```
PAYEASE_PRIVATIVE_KEY=
PAYEASE_PUBLIC_KEY=
PAYEASE_MERCHAN_ID=
PAYEASE_PASSWORD=
```

#### 使用
```shell
<?php

use Hedeqiang\PayEase\Pay;
use Hyperf\Utils\ApplicationContext;

// 请求
response = ApplicationContext::getContainer()->get(Pay::class)->request($uri,$parmas);

// 回调
$response = ApplicationContext::getContainer()->get(Pay::class)->handleNotify();
```

## 在 Yii2 中使用
#### 配置
在 `Yii2` 配置文件 `config/main.php` 的 `components` 中添加:
```php
'components' => [
    // ...
    'pay' => [
        'class' => 'Hedeqiang\PayEase\YiiPay',
        'options' => [
            'privateKey' => '/private.pfx',
            'publicKey' => '//server.cer',
            'merchantId' => '890000593',
            'password' => '123456',
        ],
    ],
    // ...
]
```

#### 使用
```php

Yii::$app->response->format = Response::FORMAT_JSON;

// 请求
$results = Yii::$app->pay->getPay()->request($uri,$params);
// 回调
$results = Yii::$app->pay->getPay()->handleNotify();
```

## Project supported by JetBrains

Many thanks to Jetbrains for kindly providing a license for me to work on this and other open-source projects.

[![](https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.svg)](https://www.jetbrains.com/?from=https://github.com/hedeqiang)


## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/hedeqiang/payease/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/hedeqiang/payease/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT

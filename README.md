# 首信易支付 SDK for PHP

[![Latest Stable Version](https://poser.pugx.org/hedeqiang/yizhifu/v)](//packagist.org/packages/hedeqiang/yizhifu)
[![Total Downloads](https://poser.pugx.org/hedeqiang/yizhifu/downloads)](//packagist.org/packages/hedeqiang/yizhifu)
[![Latest Unstable Version](https://poser.pugx.org/hedeqiang/yizhifu/v/unstable)](//packagist.org/packages/hedeqiang/yizhifu)
[![License](https://poser.pugx.org/hedeqiang/yizhifu/license)](//packagist.org/packages/hedeqiang/yizhifu)


参考文档 [首信易支付](https://demo.yizhifubj.com/Development/showdoc-master/web/#/5?page_id=242)

## Installing

```shell
$ composer require hedeqiang/yizhifu -vvv
```

## Usage
```shell
require __DIR__ .'/vendor/autoload.php';
use Hedeqiang\Yizhifu\Pay;
$app = new Pay([
    'privateKey' => '/parth/client.pfx',
    'publicKey'  => 'path/test.cer',
    'merchantId' => '890000593',
    'password'   => '123456',
]);
```

> 本 SDK 已经处理好 hmac 签名，用户不需要传递此参数

### 立即下单
```shell
$url = 'onlinePay/order';

$parmas = [
    "callbackUrl" =>"https://demo.5upay.com/sdk/callback",
    "clientIp" =>"10.101.10.10",
    "hmac" =>null,
    "merchantId" =>"890000593",
    "notifyUrl" =>"https://demo.5upay.com/sdk/onlinepay/notify",
    "orderAmount" =>"1",
    "orderCurrency" =>"CNY",
    "payer" => new \stdClass(),
//    "payer" => [
//        "bankCardNum" =>"6217000xxxxx",
//        "email" =>"laravel_code@163.com",
//        "idNum" =>"xxxx",
//        "idType" =>"IDCARD",
//        "name" =>"xxx",
//        "phoneNum" =>"xxx"
//    ],
    // "paymentModeCode" =>"BANK_CARD-B2C-ICBC-P2P",
    "productDetails" =>[
        [
            "amount" =>"1",
            "description" =>"黑色64G",
            "name" =>"IPHONE6",
            "quantity" =>"100",
            "receiver" =>"张三"
        ]
    ],
    "remark" =>"备注",
    "requestId" =>time(),
    "timeout" =>"10"
];
$app->request($url,$params)
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
$url = "onlinePay/query";
$params = [
    'merchantId' => '890000593',
    'requestId' => '1653659465'
];

$sreult = $app->request($url,$params);
```


## 在 Laravel 中使用
#### 发布配置文件
```php
php artisan vendor:publish --tag=yizhifu
or 
php artisan vendor:publish --provider="Hedeqiang\Yizhifu\ServiceProvider"
```
##### 编写 .env 文件
```
YIZHIFU_PRIVATIVE_KEY=
YIZHIFU_PUBLIC_KEY=
YIZHIFU_MERCHAN_ID=
YIZHIFU_PASSWORD=
```

### 使用

#### 服务名访问
```php
public function index()
{
    return app('yizhifu')->request($url,$params);
}
```

#### Facades 门面使用(可以提示)
```php
use Hedeqiang\Yizhifu\Facades\Pay;

public function index()
{
   return Pay::pay()->request($url,$params)
}

public function notify(Request $request)
{
   $result = Pay::pay()->handleNotify();
}
```

## Project supported by JetBrains

Many thanks to Jetbrains for kindly providing a license for me to work on this and other open-source projects.

[![](https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.svg)](https://www.jetbrains.com/?from=https://github.com/hedeqiang)


## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/hedeqiang/yizhifu/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/hedeqiang/yizhifu/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT

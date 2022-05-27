<?php

namespace Hedeqiang\Yizhifu;

use GuzzleHttp\Client;
use Hedeqiang\Yizhifu\Support\Config;
use GuzzleHttp\Psr7\ServerRequest;
use Hedeqiang\Yizhifu\Traits\Utils;
use Psr\Http\Message\ServerRequestInterface;

class Pay
{
    use Utils;
    protected Config $config;

    protected $guzzleOptions = [];

    const ENDPOINT_TEMPLATE = 'https://apis.5upay.com/%s';


    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    protected function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    /**
     * 按照 键名 对关联数组进行升序排序：
     * @param $params
     * @return array
     */
    protected function getArr($params): array
    {
        $data = [];
        foreach ($params as $k => $var) {
            if (is_scalar($var) && $var !== '') {//如果给出的变量参数 var 是一个标量，is_scalar() 返回 TRUE，否则返回 FALSE。标量变量是指那些包含了 integer、float、string 或 boolean的变量，而 array、object 和 resource 则不是标量。
                $data[$k] = $var;
            } elseif (is_object($var)) {
                $data[$k] = array_filter((array)$var);
            } elseif (is_array($var)) {
                $data[$k] = array_filter($var);
            }
            if (empty($data[$k])) {
                unset($data[$k]);
            }
        }

        ksort($data);
        return $data;
    }

    protected function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }

    /**
     * @param $path
     * @param $params
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request($path, $params)
    {
        $data = $this->getArr($params);

        $hmacSource = $this->buildJson($data);
        $sha1mac = sha1($hmacSource, true); //SHA1加密

        $privateKey = $this->config->get('privateKey');
        $password = $this->config->get('password');
        $hmac = $this->rsaPrivateSign($sha1mac,$privateKey,$password);

        $hmacarr = [];
        $hmacarr["hmac"] = $hmac;
        $arr_t = (array_merge($params, $hmacarr)); //合并数组

        $json_str = json_encode($arr_t, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  //将数组转成JSON
        $rands = $this->getRandomStr();
        $data = $this->aesEncrypt($rands, $json_str);

        $encryptKey = $this->rsaPublicEncode($this->config->get('publicKey'),$rands);

        $response = $this->getHttpClient()->post($this->buildEndpoint($path), [
            'headers' => [
                'Content-Type' => 'application/vnd.5upay-v3.0+json',
                'encryptKey'   => $encryptKey,
                'merchantId'   => $this->config->get('merchantId'),
                'requestId'    => time() . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            ],
            'body'    => $data
        ]);

        $contents = json_decode($response->getBody()->getContents(), true);

        $encryptKey = $response->getHeader('encryptKey')[0];
        $secret_key = $this->rsaPrivateDecode($encryptKey,$this->config->get('privateKey'),$this->config->get('password'));

        return $this->aesDecrypt($contents['data'], $secret_key);
    }

    /**
     * @return array|false
     */
    public function handleNotify()
    {
        $request = $this->getCallbackParams();
        $response = $request->getBody()->getContents();
        $response = json_decode($response, true);
        $post = $response;
        $post['encryptKey'] = $request->getHeader('encryptKey')[0];
        $post['merchantId'] = $request->getHeader('merchantId')[0];

        $encryptKey = $post['encryptKey'];
        $decrypted = $this->rsaPrivateDecode($encryptKey,$this->config->get('privateKey'),$this->config->get('password'));

        $encrypt_str = $this->aesDecrypt($response['data'],$decrypted);

        $encrypt_str = $this->clearBlank($encrypt_str);
        $hmac = $encrypt_str['hmac'];
        unset($encrypt_str['hmac']); //去除数组的一个hmac元素
        ksort($encrypt_str);

        $hmacSource = $this->buildJson($encrypt_str);
        $sha1mac = sha1($hmacSource, true);
        $verifyKeyPath = $this->config->get('publicKey');
        $verify = $this->rsaPubilcSign($sha1mac,$verifyKeyPath,$hmac);
        if ($verify === 1) {
            return $encrypt_str;
        } else {
            return false;
        }
    }


    /**
     * @param array|ServerRequestInterface|null $contents
     */
    protected function getCallbackParams($contents = null): ServerRequestInterface
    {
        if (is_array($contents) && isset($contents['body']) && isset($contents['headers'])) {
            return new ServerRequest('POST', 'http://localhost', $contents['headers'], $contents['body']);
        }

        if (is_array($contents)) {
            return new ServerRequest('POST', 'http://localhost', [], json_encode($contents));
        }

        if ($contents instanceof ServerRequestInterface) {
            return $contents;
        }

        return ServerRequest::fromGlobals();
    }

    /**
     *
     * @param string $path
     * @return string
     */
    protected function buildEndpoint(string $path): string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $path);
    }


}
